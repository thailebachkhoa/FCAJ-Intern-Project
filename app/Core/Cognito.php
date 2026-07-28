<?php
// Location: app/Core/Cognito.php

use Firebase\JWT\JWT;
use Firebase\JWT\JWK;

/**
 * Xử lý đăng nhập qua Cognito Hosted UI (Google là Identity Provider)
 * và verify id_token trả về sau khi login thành công.
 *
 * Cần các biến môi trường trong .env:
 *   COGNITO_REGION, COGNITO_USER_POOL_ID,
 *   COGNITO_APP_CLIENT_ID, COGNITO_APP_CLIENT_SECRET (có thể để trống),
 *   COGNITO_DOMAIN, COGNITO_REDIRECT_URI
 */
class Cognito
{
    private static function domain()
    {
        $prefix = $_ENV['COGNITO_DOMAIN'] ?? '';
        $region = $_ENV['COGNITO_REGION'] ?? '';
        return "https://{$prefix}.auth.{$region}.amazoncognito.com";
    }

    private static function clientId()
    {
        return $_ENV['COGNITO_APP_CLIENT_ID'] ?? '';
    }

    private static function clientSecret()
    {
        return $_ENV['COGNITO_APP_CLIENT_SECRET'] ?? '';
    }

    private static function redirectUri()
    {
        return $_ENV['COGNITO_REDIRECT_URI'] ?? '';
    }

    private static function region()
    {
        return $_ENV['COGNITO_REGION'] ?? '';
    }

    private static function userPoolId()
    {
        return $_ENV['COGNITO_USER_POOL_ID'] ?? '';
    }

    /**
     * URL đưa thẳng người dùng sang màn hình đăng nhập Google
     * (identity_provider=Google bỏ qua luôn màn chọn của Cognito Hosted UI).
     */
    public static function loginUrl()
    {
        $state = bin2hex(random_bytes(16));
        $_SESSION['oauth_state'] = $state;

        $params = [
            'identity_provider' => 'Google',
            'redirect_uri'      => self::redirectUri(),
            'response_type'     => 'code',
            'client_id'         => self::clientId(),
            'scope'             => 'openid email profile',
            'state'             => $state,
        ];

        return self::domain() . '/oauth2/authorize?' . http_build_query($params);
    }

    /**
     * URL đăng xuất khỏi phiên Cognito (tuỳ chọn dùng thêm khi logout).
     * $redirectTo phải nằm trong danh sách "Allowed sign-out URLs" của App client.
     */
    public static function logoutUrl($redirectTo)
    {
        $params = [
            'client_id'  => self::clientId(),
            'logout_uri' => $redirectTo,
        ];
        return self::domain() . '/logout?' . http_build_query($params);
    }

    /**
     * Đổi authorization code (query string ?code=...) lấy id_token/access_token.
     * @throws Exception nếu Cognito từ chối hoặc không gọi được mạng
     */
    public static function exchangeCodeForTokens($code)
    {
        $body = [
            'grant_type'   => 'authorization_code',
            'client_id'    => self::clientId(),
            'code'         => $code,
            'redirect_uri' => self::redirectUri(),
        ];

        $headers = ['Content-Type: application/x-www-form-urlencoded'];
        if (self::clientSecret() !== '') {
            $headers[] = 'Authorization: Basic ' . base64_encode(self::clientId() . ':' . self::clientSecret());
        }

        $ch = curl_init(self::domain() . '/oauth2/token');
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => http_build_query($body),
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 10,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr  = curl_error($ch);
        curl_close($ch);

        if ($response === false) {
            throw new Exception('Không gọi được Cognito token endpoint: ' . $curlErr);
        }

        $data = json_decode($response, true);
        if ($httpCode !== 200 || !isset($data['id_token'])) {
            throw new Exception('Cognito từ chối đổi code lấy token (HTTP ' . $httpCode . '): ' . $response);
        }

        return $data;
    }

    /**
     * Lấy JWKS (public key) của User Pool, cache ra file 24h để đỡ gọi mạng
     * mỗi lần có người đăng nhập.
     */
    private static function jwks()
    {
        $cacheFile = STORAGE_PATH . '/cache/cognito_jwks.json';

        if (file_exists($cacheFile) && (time() - filemtime($cacheFile) < 86400)) {
            return json_decode(file_get_contents($cacheFile), true);
        }

        $url  = 'https://cognito-idp.' . self::region() . '.amazonaws.com/' . self::userPoolId() . '/.well-known/jwks.json';
        $json = @file_get_contents($url);

        if ($json === false) {
            if (file_exists($cacheFile)) {
                return json_decode(file_get_contents($cacheFile), true);
            }
            throw new Exception('Không tải được JWKS từ Cognito.');
        }

        file_put_contents($cacheFile, $json);
        return json_decode($json, true);
    }

    /**
     * Verify chữ ký + claims cơ bản (iss, aud, token_use) của id_token.
     * @return array payload đã giải mã
     * @throws Exception nếu token không hợp lệ
     */
    public static function verifyIdToken($idToken)
    {
        $keySet  = JWK::parseKeySet(self::jwks());
        $decoded = JWT::decode($idToken, $keySet);
        $payload = (array) $decoded;

        $expectedIss = 'https://cognito-idp.' . self::region() . '.amazonaws.com/' . self::userPoolId();

        if (($payload['iss'] ?? '') !== $expectedIss) {
            throw new Exception('id_token sai issuer.');
        }
        if (($payload['aud'] ?? '') !== self::clientId()) {
            throw new Exception('id_token sai audience (client_id).');
        }
        if (($payload['token_use'] ?? '') !== 'id') {
            throw new Exception('Token không phải id_token.');
        }

        return $payload;
    }

    public static function groups($payload)
    {
        return $payload['cognito:groups'] ?? [];
    }

    public static function isAdminGroup($payload)
    {
        return in_array('Admin', self::groups($payload), true);
    }
}