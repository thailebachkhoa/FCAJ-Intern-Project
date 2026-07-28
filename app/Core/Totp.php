<?php
// Location: app/Core/Totp.php

/**
 * Cài đặt TOTP (RFC 6238) tự viết, không phụ thuộc thư viện ngoài.
 * Tương thích Google Authenticator / Authy / Microsoft Authenticator.
 * Dùng để bắt buộc Admin xác thực 2 bước sau khi đăng nhập Google.
 */
class Totp
{
    private const PERIOD = 30;
    private const DIGITS = 6;
    private const SECRET_BYTES = 20; // 160-bit, khuyến nghị RFC 4226

    /** Sinh secret ngẫu nhiên, encode Base32 (Google Authenticator cần dạng này) */
    public static function generateSecret()
    {
        return self::base32Encode(random_bytes(self::SECRET_BYTES));
    }

    /** Sinh URI otpauth:// để vẽ QR code cho app Authenticator quét */
    public static function provisioningUri($secret, $accountEmail, $issuer = 'Plantify Co')
    {
        $label  = rawurlencode($issuer) . ':' . rawurlencode($accountEmail);
        $params = http_build_query([
            'secret'    => $secret,
            'issuer'    => $issuer,
            'algorithm' => 'SHA1',
            'digits'    => self::DIGITS,
            'period'    => self::PERIOD,
        ]);
        return "otpauth://totp/{$label}?{$params}";
    }

    /**
     * Verify mã 6 số người dùng nhập.
     * $window = 1 nghĩa là chấp nhận lệch 1 khung 30s trước/sau,
     * bù cho lệch giờ nhẹ giữa điện thoại và server.
     */
    public static function verify($secret, $code, $window = 1)
    {
        $code = preg_replace('/\s+/', '', (string) $code);
        if ($secret === '' || !preg_match('/^\d{6}$/', $code)) {
            return false;
        }

        $currentSlice = (int) floor(time() / self::PERIOD);

        for ($i = -$window; $i <= $window; $i++) {
            if (hash_equals(self::codeAt($secret, $currentSlice + $i), $code)) {
                return true;
            }
        }
        return false;
    }

    private static function codeAt($secret, $timeSlice)
    {
        $key  = self::base32Decode($secret);
        $time = pack('N*', 0) . pack('N*', $timeSlice); // counter 8 byte big-endian
        $hash = hash_hmac('sha1', $time, $key, true);

        $offset = ord($hash[19]) & 0x0F;
        $truncated = ((ord($hash[$offset]) & 0x7F) << 24)
            | ((ord($hash[$offset + 1]) & 0xFF) << 16)
            | ((ord($hash[$offset + 2]) & 0xFF) << 8)
            | (ord($hash[$offset + 3]) & 0xFF);

        $code = $truncated % (10 ** self::DIGITS);
        return str_pad((string) $code, self::DIGITS, '0', STR_PAD_LEFT);
    }

    private static function base32Encode($binary)
    {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $bits = '';
        foreach (str_split($binary) as $char) {
            $bits .= str_pad(decbin(ord($char)), 8, '0', STR_PAD_LEFT);
        }
        $output = '';
        foreach (str_split($bits, 5) as $chunk) {
            if (strlen($chunk) < 5) {
                $chunk = str_pad($chunk, 5, '0', STR_PAD_RIGHT);
            }
            $output .= $alphabet[bindec($chunk)];
        }
        return $output;
    }

    private static function base32Decode($base32)
    {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $base32 = strtoupper((string) $base32);
        $bits = '';
        foreach (str_split($base32) as $char) {
            $pos = strpos($alphabet, $char);
            if ($pos === false) {
                continue;
            }
            $bits .= str_pad(decbin($pos), 5, '0', STR_PAD_LEFT);
        }
        $binary = '';
        foreach (str_split($bits, 8) as $byte) {
            if (strlen($byte) === 8) {
                $binary .= chr(bindec($byte));
            }
        }
        return $binary;
    }
}