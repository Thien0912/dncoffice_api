<?php 
if(!function_exists('verifyauthtoken'))
{
    function verifyauthtoken($token)
    {
        $jwt = new JWT();
        $JwtSecrectKey = "@cntt@dhnct@2023";
        $decoded_token = $jwt->decode($token, $JwtSecrectKey, 'HS256');
        return $decoded_token;
    }
}