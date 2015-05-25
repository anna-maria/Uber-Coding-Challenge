<?php
require '../vendor/autoload.php';

$app = new \Slim\Slim();

// Get database credentials and connection
$dbopts = parse_url(getenv('DATABASE_URL'));
if (empty($dbopts['path'])) {
    // Used for local
    $dbopts = parse_url(require '../env.php');
}
try {
    $db = new PDO('pgsql:dbname='.ltrim($dbopts["path"],'/').';host='.$dbopts["host"], $dbopts["user"], $dbopts["pass"]);
} catch(PDOException $e) {
    error_log('Database connection error.');
}

// Display map page to interact with API
$app->get('/', function () {
    echo file_get_contents('../views/index.html');
});

// Retrieve all movies - also used for autocomplete with name parameter
$app->get('/movies', function () use ($app,$db) {
    // Get extra query (return fields, sort, autocomplete name) if applicable
    $fields = $app->request->params('fields');
    $name = $app->request->params('name');
    $sort = $app->request->params('sort');
    $movieLocation = null;

    $movieLocation = findMoviesBy($app, $db, $fields, $sort, $name);

    // Build proper movie response 
    if ($movieLocation) {
        $finalMovies = $allMovies = $movies = [];
        foreach ($movieLocation as $movie) {
            if (!isset($allMovies[$movie->name])) {
                if (!empty($movies)) {
                    // Add to final response array after all locations are added to a specific movie
                    $finalMovies[] = $movies;
                    $movies = [];
                }
                // New movie name
                $movies['id'] = $movie->id;
                $movies['name'] = $movie->name;
                if (isset($movie->lat) && isset($movie->lng)) {
                    $movies['location'][] = ['lat' => $movie->lat, 'lng' => $movie->lng];
                }
                $allMovies[$movie->name] = 1;
            } else {
                if (isset($movie->lat) && isset($movie->lng)) {
                    // Add to lat/lng of an existing movie
                    $movies['locations'][] = ['lat' => $movie->lat, 'lng' => $movie->lng];
                }

            }
        }

        // Set the last $movie to $finalMovies
        $finalMovies[] = $movies;

        $app->response->setStatus(200);
        $app->response->headers->set('Content-Type', 'application/json');
        echo json_encode($finalMovies);
    } else {
        $app->response->setStatus(404);
        echo json_encode(['status' => false, 'message' => 'No movies are found.']);
    }
});

// Query the database for movies using fields, sort, or name filtering if applicable
function findMoviesBy($app, $db, $fields, $sort, $name) {
    $where = $orderBy = '';
    $params = [];

    // Validation for fields, sort
    $selectBy = explode(',', $fields);
    if (count($selectBy) > 1 && !in_array('name', $selectBy)) {
        $app->response->setStatus(404);
        echo json_encode(['status' => false, 'message' => 'Field is not valid.']);
    } else {
        $selectByOrig = ['m.name'];
    }
    if ($sort) {
        $sortBy = explode(',', $sort);
        if (!(in_array('id', $sortBy) || in_array('name', $sortBy))) {
            $app->response->setStatus(404);
            echo json_encode(['status' => false, 'message' => 'Sort is not valid.']);
        }
    }

    // Build full sql query with correct params
    if ($fields) {
        $select = "SELECT m.id, m.name";
    } else {
        $select = "SELECT m.id, m.name, l.lat, l.lng";
    }
    $from = " FROM movie m
             INNER JOIN movie_location ml ON m.id = ml.movie_id
             INNER JOIN location l ON ml.location_id = l.id";
    if ($name) {
        $where = " WHERE m.name ILIKE :term AND m.deleted_at IS NULL";
        $params['term'] = $name . '%';
    }
    if ($sort) {
        foreach ($sortBy as &$s) {
            $s = 'm.'. $s .' DESC';
        }
        $orderBy = " ORDER BY " . implode(',', $sortBy);
    }

    $sql = $select . $from . $where . $orderBy;

    $stmt = $db->prepare($sql);
    $stmt->execute($params);

    return $stmt->fetchAll(PDO::FETCH_OBJ);  
}

// Retrieve a specific movie
$app->get('/movies/:id', function ($id) use ($app,$db) {
    // Valication for id
    if (!isset($id) || !is_numeric($id) || $id <= 0) {
        $app->response->setStatus(400);
        echo json_encode(['status' => false, 'message' => 'Movie ID is not valid.']);
    }

    $sql = "SELECT m.id, m.name, l.lat, l.lng FROM movie m
            LEFT JOIN movie_location ml ON m.id = ml.movie_id
            LEFT JOIN location l ON ml.location_id = l.id
            WHERE m.id = :id";
    $stmt = $db->prepare($sql);
    $stmt->bindParam(':id', $id);
    $stmt->execute();

    $movieLocation = $stmt->fetchAll(PDO::FETCH_OBJ);

    if ($movieLocation) {
        // Build proper movie response
        $finalMovie = $movies = [];
        foreach ($movieLocation as $movie) {
            $movies['id'] = $movie->id;
            $movies['name'] = $movie->name;
            if (isset($movie->lat) && isset($movie->lng)) {
                $movies['locations'][] = ['lat' => $movie->lat, 'lng' => $movie->lng];
            }
            $allMovies[$movie->name] = 1;
        }

        // Set the last $movie to $finalMovies
        $finalMovie[] = $movies;

        $app->response->setStatus(200);
        $app->response->headers->set('Content-Type', 'application/json');
        echo json_encode($finalMovie);
    } else {
        $app->response->setStatus(404);
        echo json_encode(['status' => false, 'message' => 'Movie ID: ' . $id . ' is not found.']);
    }
});

// Create a movie
$app->post('/movies', function() use ($app, $db){
    $body = $app->request->getBody();
    $postMovie = json_decode($body, true);

    // Insert new movie in database
    $sql = "INSERT INTO movie (name)
            VALUES (:name);";
    $stmt = $db->prepare($sql);
    $stmt->bindParam(':name', $postMovie['name']);
    $stmt->execute();
    $lastMovieId = $db->lastInsertId('movie_id_seq');

    if ($lastMovieId) {
        $app->response->setStatus(201);
        $app->response->headers->set('Content-Type', 'application/json');

        // Add id to postMovie and return newly created movie resource
        $postMovie['id'] = $lastMovieId;
        echo json_encode($postMovie);
    } else {
        $app->response->setStatus(400);
    }
});

// Update a movie 
$app->put('/movies/:id', function ($id) use($app, $db){
    // Valication for id
    if (!isset($id) || !is_numeric($id) || $id <= 0) {
        $app->response->setStatus(400);
        echo json_encode(['status' => false, 'message' => 'Movie ID is not valid.']);
    }

    $body = $app->request->getBody();
    $putMovie = json_decode($body, true);

    // Query database to see if the movie actually exists
    $sql = "SELECT *
            FROM movie
            WHERE id = :id;";
    $stmt = $db->prepare($sql);
    $stmt->bindParam(':id', $id);
    $stmt->execute();
    $movie = $stmt->fetch();

    if ($movie) {
        // Update movie name in database
        $sql = "UPDATE movie
                SET name = :name
                WHERE id = :id;";
        $stmt = $db->prepare($sql);
        $stmt->bindParam(':name', $putMovie['name']);
        $stmt->bindParam(':id', $movie['id']);
        $stmt->execute();

        $app->response->setStatus(200);
    } else {
        // Movie not found with that id
        $app->response->setStatus(404);
    }
});

// Delete a movie
$app->delete('/movies/:id', function ($id) use ($app, $db){
    // Validation for id
    if (!isset($id) || !is_numeric($id) || $id <= 0) {
        $app->response->setStatus(400);
        echo json_encode(['status' => false, 'message' => 'Movie ID is not valid.']);
    }

    // Update deleted_at with current timestamp to set movie as deleted
    $sql = "UPDATE movie m
            SET deleted_at = now()
            WHERE m.id = :id";
    $stmt = $db->prepare($sql);
    $stmt->bindParam(':id', $id);
    $stmt->execute();

    $app->response->setStatus(204);        
});

$app->run();