<?php
    require '../vendor/autoload.php';

    $dbopts = parse_url(getenv('DATABASE_URL'));
    if (empty($dbopts['path'])) {
        $dbopts = parse_url(require '../env.php');
    }

    $app = new \Slim\Slim();

    try {
        $dbh = new PDO('pgsql:dbname='.ltrim($dbopts["path"],'/').';host='.$dbopts["host"], $dbopts["user"], $dbopts["pass"]);
    } catch(PDOException $e) {
        echo $e->getMessage();
    }

    // Retrieve all movies - also used for autocomplete with name parameter
    $app->get('/movies', function () use ($app,$dbh) {
        $autocomplete = $app->request->params('name');
        if ($autocomplete) {
            $sql = "SELECT id, name FROM movie
                    WHERE name ILIKE '$autocomplete%';";
        } else {
            $sql = "SELECT m.name, l.lat, l.lng FROM movie m
                    INNER JOIN movie_location ml ON m.id = ml.movie_id
                    INNER JOIN location l ON ml.location_id = l.id";
        }
        $stmt = $dbh->prepare($sql);
        $stmt->execute();

        $movieLocation = $stmt->fetchAll(PDO::FETCH_OBJ);

        if ($movieLocation) {
            $app->response->setStatus(200);
            $app->response->headers->set('Content-Type', 'application/json');
            echo json_encode($movieLocation);
        } else {
            $app->response->setStatus(404);
            echo json_encode(['status' => false, 'message' => 'No movies are found.']);
        }
    });

    // Retrieve a specific movie
    $app->get('/movies/:id', function ($id) use ($app,$dbh) {
        $sql = "SELECT m.name, l.lat, l.lng FROM movie m
                INNER JOIN movie_location ml ON m.id = ml.movie_id
                INNER JOIN location l ON ml.location_id = l.id
                WHERE m.id = :id";
        $stmt = $dbh->prepare($sql);
        $stmt->bindParam(':id', $id);
        $stmt->execute();

        $movieLocation = $stmt->fetchAll(PDO::FETCH_OBJ);

        if ($movieLocation) {
            $app->response->setStatus(200);
            $app->response->headers->set('Content-Type', 'application/json');
            echo json_encode($movieLocation);
        } else {
            $app->response->setStatus(404);
            echo json_encode(['status' => false, 'message' => 'Movie ID: ' . $id . ' is not found.']);
        }
    });

    // Future POST, PUT, and DELETE functionality
    $app->post('/movies', function() {
    });

    $app->put('/movies/:id', function ($id) {
    });

    $app->delete('/movies/:id', function ($id) {
    });

    $app->run();