<?php

use Slim\Http\Request;

require_once __DIR__ . '/environ.php';

/** Validate a given authentication token to make sure it was the official client that sent the request.
 *  The auth key is generated by appending a pre-shared key to the current timestamp (modded to the closest 20-second
 *  interval) and then one-way encoded with md5. A key is valid if it matches the one generated server-side.
 *  Acknowledging that requests take time, both the current 20-second interval and the previous one will be accepted.
 *  Thus keys are valid for effectively a maximum of 40 seconds.
 *  If the application is not in prod, the pre-shared key will be accepted as plaintext too.
 * @param $received_key string The value of the auth header received from the requester.
 * @return bool True if the key is valid. False otherwise.
 */
function validate_auth($received_key){

    $current_seconds = time();
    $gap1 = $current_seconds - ($current_seconds % 20);
    $gap2 = $gap1 + 20;

    $key1 = md5($gap1.CLIENT_KEY);
    $key2 = md5($gap2.CLIENT_KEY);

    return (!APP_IN_PROD && $received_key == CLIENT_KEY) ||
        $received_key == $key1 || $received_key == $key2;

}


function _bad_request($code, $msg){
    return [
        'success' => false,
        'code' => $code,
        'response' => ['msg' => $msg]
    ];
}

/** Checks the following things:
 *  - Authorisation is legit.
 *  - If not GET, the body is in JSON
 * @param Request $req The request to be checked
 * @return array Return the parsed body if all checks passed. Otherwise, an assoc array with information on what went
 *  wrong.
 */
function validate_request(Request $req){

    $auth_header = $req->getHeader('Auth');
    if (sizeof($auth_header) == 0){
        return _bad_request(400, 'Auth header incorrect format');
    }

    if (!validate_auth($auth_header[0])){
        return _bad_request(401, 'Bad authentication token');
    }

    if ($req->getMethod() != 'GET'){

        $body = $req->getParsedBody();

        if ($body == null)
            return _bad_request(400, 'Unparsable body.');

        return [
            'success' => true,
            'body' => $body
        ];

    }else { //GET

        return ['success' => true];

    }

}


