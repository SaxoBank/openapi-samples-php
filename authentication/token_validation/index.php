<?php

/*
 *  This sample demonstrates the validation of the hashes in the token.
 *
 *  Redirect: https://saxobank.com?code=Bvi-SeI5dAR74VM1H75EbSVBci8&iss=https%3A%2F%2Fauth-ext.ssocpoc.one.eu41d.inf.iitech.dk%3A443%2Fam%2Foauth2%2Frealms%2Froot%2Frealms%2Fdca&state=Hallo&client_id=STGOApp
 *  Code: Bvi-SeI5dAR74VM1H75EbSVBci8
 *  State: Hallo
 *  Token: eyJ0eXAiOiJKV1QiLCJraWQiOiJnVVRBa3hiL3pVRTg2OVMxcTdDOGxXUytyUms9IiwiYWxnIjoiUlMyNTYifQ.eyJhdF9oYXNoIjoidlBEMUhhY3hXeVJHZEZQYzRwU1lDUSIsInN1YiI6Iih1c3IheHdvdWZ1c2VyMykiLCJpaWQiOiJQdmtDcllHa2RQNncwN058YXNSU3F1VlRkS3gtUDhsbTlwY0xIU1JjTm5kT3AxY1Z3b1dxS3I4LVhGOWVxb1pZIiwiYXVkaXRUcmFja2luZ0lkIjoiMWVlM2NkY2ItMmU0ZS00YThmLThjMzItNTFkY2VjMjdmMGFlLTMyMjM5NCIsImlzcyI6Imh0dHBzOi8vYXV0aC1leHQuc3NvY3BvYy5vbmUuZXU0MWQuaW5mLmlpdGVjaC5kazo0NDMvYW0vb2F1dGgyL2RjYSIsInRva2VuTmFtZSI6ImlkX3Rva2VuIiwic2lkIjoiNWM1ZDQwYjU2MjAwNGZlOGJlYjZhNDAzYWViZTEwNjQiLCJhY3IiOiIwIiwidWlkIjoiclIwTUd3d21ybnFjWVBTZkZCYm8zZz09IiwiYXpwIjoiU1RHT0FwcCIsImF1dGhfdGltZSI6MTY3OTY0NDQzNSwib2FsIjoiMkYiLCJleHAiOjE2Nzk2NDgwNTEsImlhdCI6MTY3OTY0NDQ1MSwiZXJyIjoiIiwidHJkIjoiLTEiLCJzdWJuYW1lIjoieHdvdWZ1c2VyMyIsInVuaWlkIjoiRDg3RUM2QzItOUU0Ny00MTYyLTEwOEItMDhEOENDM0Q4NDFEIiwibXVpZCI6IjIxNDczNDMzMzMiLCJhdWQiOiJTVEdPQXBwIiwiY19oYXNoIjoiMVN1c3k0Vkh1S3Vua3NCSnBEcWJuUSIsIm9yZy5mb3JnZXJvY2sub3BlbmlkY29ubmVjdC5vcHMiOiJfRWo2cEhpajZVTU9zcjZKajIyang3WHVKNU0iLCJzX2hhc2giOiJkVGFTN0RhdHRNZVV5WE9VWHJLcG5BIiwibmFtZSI6Ik4vQSIsInJlYWxtIjoiL2RjYSIsInRva2VuVHlwZSI6IkpXVFRva2VuIiwiYWlkIjoiNyIsImZhbWlseV9uYW1lIjoiTi9BIiwiY2lkIjoidXxseXQ5NFBrT0F4cERNME50QnFndz09In0.kBOc_t1Zxkohqk-oi1ZmEZVL1wGd_jkA04d6xJbkqs7FOOHZTQubNhgKFspsTXkF6qiFwjMzUyYUQWWsl_dWNNxnbpzj250iSZb1O7aW8kkFNdVoV52p036JaVnWLdnSg8u1xVI4eurs3vzBba4igUN1OkMHdSvbiBlmgHee1fsxIJ96Aa-8uuf4RCsBS1zkYT_VhCCgS0QUzVFoxcpQSDvD0X0woWBUdYOXD8pEFWW4xttvqe5GhBOK1XSVcaxjjqU14IqISDUNnTUjmjpC9DJPibDlZZ40ZLBGKCZRy5jjHYl5BDZuQjw96jGqoYlky2E04Bm7HN-HZiysQSyGoA
 *
 */

$code = 'Bvi-SeI5dAR74VM1H75EbSVBci8';
$state = 'Hallo';
$idToken = 'g-CVJPstFF5SFdEEkERztkTB65M';
$accessToken = 'eyJ0eXAiOiJKV1QiLCJraWQiOiJnVVRBa3hiL3pVRTg2OVMxcTdDOGxXUytyUms9IiwiYWxnIjoiUlMyNTYifQ.eyJhdF9oYXNoIjoidlBEMUhhY3hXeVJHZEZQYzRwU1lDUSIsInN1YiI6Iih1c3IheHdvdWZ1c2VyMykiLCJpaWQiOiJQdmtDcllHa2RQNncwN058YXNSU3F1VlRkS3gtUDhsbTlwY0xIU1JjTm5kT3AxY1Z3b1dxS3I4LVhGOWVxb1pZIiwiYXVkaXRUcmFja2luZ0lkIjoiMWVlM2NkY2ItMmU0ZS00YThmLThjMzItNTFkY2VjMjdmMGFlLTMyMjM5NCIsImlzcyI6Imh0dHBzOi8vYXV0aC1leHQuc3NvY3BvYy5vbmUuZXU0MWQuaW5mLmlpdGVjaC5kazo0NDMvYW0vb2F1dGgyL2RjYSIsInRva2VuTmFtZSI6ImlkX3Rva2VuIiwic2lkIjoiNWM1ZDQwYjU2MjAwNGZlOGJlYjZhNDAzYWViZTEwNjQiLCJhY3IiOiIwIiwidWlkIjoiclIwTUd3d21ybnFjWVBTZkZCYm8zZz09IiwiYXpwIjoiU1RHT0FwcCIsImF1dGhfdGltZSI6MTY3OTY0NDQzNSwib2FsIjoiMkYiLCJleHAiOjE2Nzk2NDgwNTEsImlhdCI6MTY3OTY0NDQ1MSwiZXJyIjoiIiwidHJkIjoiLTEiLCJzdWJuYW1lIjoieHdvdWZ1c2VyMyIsInVuaWlkIjoiRDg3RUM2QzItOUU0Ny00MTYyLTEwOEItMDhEOENDM0Q4NDFEIiwibXVpZCI6IjIxNDczNDMzMzMiLCJhdWQiOiJTVEdPQXBwIiwiY19oYXNoIjoiMVN1c3k0Vkh1S3Vua3NCSnBEcWJuUSIsIm9yZy5mb3JnZXJvY2sub3BlbmlkY29ubmVjdC5vcHMiOiJfRWo2cEhpajZVTU9zcjZKajIyang3WHVKNU0iLCJzX2hhc2giOiJkVGFTN0RhdHRNZVV5WE9VWHJLcG5BIiwibmFtZSI6Ik4vQSIsInJlYWxtIjoiL2RjYSIsInRva2VuVHlwZSI6IkpXVFRva2VuIiwiYWlkIjoiNyIsImZhbWlseV9uYW1lIjoiTi9BIiwiY2lkIjoidXxseXQ5NFBrT0F4cERNME50QnFndz09In0.kBOc_t1Zxkohqk-oi1ZmEZVL1wGd_jkA04d6xJbkqs7FOOHZTQubNhgKFspsTXkF6qiFwjMzUyYUQWWsl_dWNNxnbpzj250iSZb1O7aW8kkFNdVoV52p036JaVnWLdnSg8u1xVI4eurs3vzBba4igUN1OkMHdSvbiBlmgHee1fsxIJ96Aa-8uuf4RCsBS1zkYT_VhCCgS0QUzVFoxcpQSDvD0X0woWBUdYOXD8pEFWW4xttvqe5GhBOK1XSVcaxjjqU14IqISDUNnTUjmjpC9DJPibDlZZ40ZLBGKCZRy5jjHYl5BDZuQjw96jGqoYlky2E04Bm7HN-HZiysQSyGoA';

/**
 * This function is taken from php-Akita_OpenIDConnect
 * https://github.com/ritou/php-Akita_OpenIDConnect/blob/master/src/Akita/OpenIDConnect/Util/Base64.php
 * @param string $str The string to encode
 */
function base64_urlEncode($str) {
    $enc = base64_encode($str);
    $enc = rtrim($enc, '=');
    $enc = strtr($enc, '+/', '-_');
    return $enc;
}

/**
 * This function extracts the hash algo and doews the hashing
 * @param string $str The string to hash
 */
function getHash($str, $headerString) {
    global $accessToken;
    $header = json_decode(base64_decode($headerString));
    $hashAlgorithm = 'sha' . substr($header->alg, 2);
    echo 'Supplied hash algo in header ' . $header->alg . ' makes algo ' . $hashAlgorithm . '<br />';
    $hash = hash($hashAlgorithm, $str, true);
    $hashHalf = substr($hash, 0, strlen($hash) / 2);
    return base64_urlEncode($hashHalf);
}

/**
 * Code hash value.
 * Its value is the base64url encoding of the left-most half of the hash of the octets of the ASCII representation of the code value,
 * where the hash algorithm used is the hash algorithm used in the alg Header Parameter of the ID Token's JOSE Header.
 * For instance, if the alg is HS512, hash the code value with SHA-512, then take the left-most 256 bits and base64url encode them.
 * The c_hash value is a case sensitive string.
 * If the ID Token is issued from the Authorization Endpoint with a code, which is the case for the response_type values code id_token and code id_token token,
 * this is REQUIRED; otherwise, its inclusion is OPTIONAL.
 */
function validateCode() {
    global $accessToken;
    global $code;
    $tokenArray = explode('.', $accessToken);
    if (count($tokenArray) !== 3) {
        die('Invalid token.');
    }
    $payload = json_decode(base64_decode($tokenArray[1]));
    if (!isset($payload->c_hash)) {
        echo 'The c_hash is not available in the token. Is the token requested with a code? Or is it a refreshed token?<br /><br />';
        return;
    }
    $hash = getHash($code, $tokenArray[0]);
    echo 'c_hash claim: ' . $payload->c_hash . '<br />';
    echo 'Hashed code: ' . $hash . '<br />';
    echo (
        $payload->c_hash === $hash
        ? 'The c_hash claim matches with the hashed code. Valid!'
        : 'There is an issue with this token. Don\'t trust it!'
    );
    echo '<br /><br />';
}

/**
 * State hash value.
 * Its value is the base64url encoding of the left-most half of the hash of the octets of the ASCII representation of the state value,
 * where the hash algorithm used is the hash algorithm used in the alg header parameter of the ID Token's JOSE header.
 * For instance, if the alg is HS512, hash the state value with SHA-512, then take the left-most 256 bits and base64url encode them.
 * The s_hash value is a case sensitive string.
 */
function validateState() {
    global $accessToken;
    global $state;
    $tokenArray = explode('.', $accessToken);
    if (count($tokenArray) !== 3) {
        die('Invalid token.');
    }
    $payload = json_decode(base64_decode($tokenArray[1]));
    if (!isset($payload->s_hash)) {
        echo 'The s_hash is not available in the token. Is a state supplied in the redirect URL?<br /><br />';
        return;
    }
    $hash = getHash($state, $tokenArray[0]);
    echo 's_hash claim: ' . $payload->s_hash . '<br />';
    echo 'Hashed state: ' . $hash . '<br />';
    echo (
        $payload->s_hash === $hash
        ? 'The s_hash claim matches with the hashed state. Valid!'
        : 'There is an issue with this token. Don\'t trust it!'
    );
    echo '<br /><br />';
}

/**
 * Access Token hash value.
 * Its value is the base64url encoding of the left-most half of the hash of the octets of the ASCII representation of the access_token value,
 * where the hash algorithm used is the hash algorithm used in the alg Header Parameter of the ID Token's JOSE Header.
 * For instance, if the alg is RS256, hash the access_token value with SHA-256, then take the left-most 128 bits and base64url encode them.
 * The at_hash value is a case sensitive string.
 * If the ID Token is issued from the Authorization Endpoint with an access_token value, which is the case for the response_type value code id_token token,
 * this is REQUIRED; otherwise, its inclusion is OPTIONAL.
 */
function validateToken() {
    global $accessToken;
    global $idToken;
    $tokenArray = explode('.', $accessToken);
    if (count($tokenArray) !== 3) {
        die('Invalid token.');
    }
    $payload = json_decode(base64_decode($tokenArray[1]));
    if (!isset($payload->at_hash)) {
        echo 'The at_hash is not available in the token. Is this a JWT token?<br /><br />';
        return;
    }
    $hash = getHash($idToken, $tokenArray[0]);
    echo 'at_hash claim: ' . $payload->at_hash . '<br />';
    echo 'Hashed id_token: ' . $hash . '<br />';
    echo (
        $payload->at_hash === $hash
        ? 'The at_hash claim matches with the hashed id_token. Valid!'
        : 'There is an issue with this token. Don\'t trust it!'
    );
    echo '<br /><br />';
}

validateCode();
validateState();
validateToken();
