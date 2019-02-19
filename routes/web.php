$router->group(['prefix' => 'auth'], function() use ($router) {

	$router->post('/attemp',['uses'=>'AuthController@getToken']);
	$router->post('/logout',['uses'=>'AuthController@logout']);
	$router->post('/recover',['uses'=>'AuthController@sendRecoverAccount']);
	$router->post('/recover/attemp',['uses'=>'AuthController@recoverAttemp']);


	$router->group(['prefix' => 'register'], function() use ($router) {

		$router->post('/talent', ['uses' => 'AuthController@registerTalent']);
		$router->get('/activate/{id}/{email_token}',['uses'=>'AuthController@activateAccount']);

	});

})
