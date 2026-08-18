<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class StrongPassword implements Rule
{
    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        return $this->isStrong($value);
    }

    /**
     * Check if password meets all strength requirements.
     *
     * @param  string  $password
     * @return bool
     */
    private function isStrong($password)
    {
        // Check minimum length (8 characters)
        if (strlen($password) < 8) {
            return false;
        }

        // Check for uppercase letters
        if (!preg_match('/[A-Z]/', $password)) {
            return false;
        }

        // Check for lowercase letters
        if (!preg_match('/[a-z]/', $password)) {
            return false;
        }

        // Check for numbers
        if (!preg_match('/[0-9]/', $password)) {
            return false;
        }

        // Check for special characters
        if (!preg_match('/[!@#$%^&*()_+\-=\[\]{};:\'",.<>?\/\\|`~]/', $password)) {
            return false;
        }

        // Reject sequential and predictable passwords
        if ($this->isSequentialOrPredictable($password)) {
            return false;
        }

        // Reject common dictionary words
        if ($this->containsCommonWords($password)) {
            return false;
        }

        return true;
    }

    /**
     * Check if password contains sequential or predictable patterns.
     *
     * @param  string  $password
     * @return bool
     */
    private function isSequentialOrPredictable($password)
    {
        $lower = strtolower($password);

        // Check for sequential numbers (e.g., 123456, 12345678)
        if (preg_match('/(?:0123|1234|2345|3456|4567|5678|6789|7890|01234|12345|23456|34567|45678|56789|67890|012345|123456|234567|345678|456789|567890|0123456|1234567|2345678|3456789|4567890)/', $lower)) {
            return true;
        }

        // Check for reverse sequential numbers
        if (preg_match('/(?:9876|8765|7654|6543|5432|4321|3210|98765|87654|76543|65432|54321|43210|987654|876543|765432|654321|543210)/', $lower)) {
            return true;
        }

        // Check for sequential letters (e.g., abcdef, qwerty)
        if (preg_match('/(?:abcde|bcdef|cdefg|defgh|efghi|fghij|ghijk|hijkl|ijklm|jklmn|klmno|lmnop|mnopq|nopqr|opqrs|pqrst|qrstu|rstuv|stuvw|tuvwx|uvwxy|vwxyz|abcdef)/', $lower)) {
            return true;
        }

        // Check for keyboard patterns (e.g., qwerty, asdfgh, zxcvbn)
        if (preg_match('/(?:qwerty|asdfgh|zxcvbn|qwertz|dvorak|ytrewq|hgfdsa|nbvcxz)/', $lower)) {
            return true;
        }

        // Check for repeating characters (e.g., 111111, aaaaaa)
        if (preg_match('/(.)\1{5,}/', $password)) {
            return true;
        }

        // Check for repeating patterns (e.g., 121212, abcabc)
        if (preg_match('/(.{2,})\1{2,}/', $password)) {
            return true;
        }

        return false;
    }

    /**
     * Check if password contains common dictionary words.
     *
     * @param  string  $password
     * @return bool
     */
    private function containsCommonWords($password)
    {
        $commonWords = [
            'password', 'admin', 'welcome', 'letmein', 'monkey', 'dragon',
            'master', 'sunshine', 'princess', 'qwerty', '123456', 'password123',
            'admin123', 'user', 'login', 'test', 'guest', 'root', 'pass',
            'system', 'change', 'default', 'secret', 'temp', 'temporary',
            'vetcare', 'clinic', 'vet', 'pet', 'animal', 'health',
        ];

        $lower = strtolower($password);

        foreach ($commonWords as $word) {
            if (strpos($lower, $word) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'Password must contain at least 8 characters, including uppercase letters, lowercase letters, numbers, and special characters. Avoid sequential patterns, keyboard sequences, repeating characters, and common dictionary words.';
    }

    /**
     * Get validation requirements for client-side display.
     *
     * @return array
     */
    public static function getRequirements()
    {
        return [
            'length' => ['label' => 'At least 8 characters', 'regex' => '/.{8,}/'],
            'uppercase' => ['label' => 'At least one uppercase letter (A-Z)', 'regex' => '/[A-Z]/'],
            'lowercase' => ['label' => 'At least one lowercase letter (a-z)', 'regex' => '/[a-z]/'],
            'number' => ['label' => 'At least one number (0-9)', 'regex' => '/[0-9]/'],
            'special' => ['label' => 'At least one special character (!@#$%^&*()_+-=[]{};\':,.<>?/\\|`~)', 'regex' => '/[!@#$%^&*()_+\-=\[\]{};:\'",.<>?\/\\|`~]/'],
        ];
    }
}
