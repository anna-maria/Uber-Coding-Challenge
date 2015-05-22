<?php
require '../vendor/autoload.php';

use Slim\Environment;

class Tests {

	public function request($method, $path, $options = []) {
		ob_start();

		Environment::mock(array_merge([
			'REQUEST_METHOD' => $method,
			'PATH_INFO' => $path,
			'SERVER_NAME' => 'slim-test.dev'
			], $options));

		$app = new \Slim\Slim();
		$this->app = $app;
		$this->request = $app->request;
		$this->response = $app->response;

		return ob_get_clean();
	}

	public function get($path, $options = []) {
		$this->request('GET', $path, $options);
	}

	public function testIndex() {
		$this->get('/');
		$this->assertEquals('200', $this->response->status());
	}
}
