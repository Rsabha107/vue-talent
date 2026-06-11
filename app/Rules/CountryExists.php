<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;
use App\Models\Country;

class CountryExists implements Rule
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
        if (empty($value)) {
            return true; // Allow empty (nullable)
        }

        return Country::where('country_name', $value)
            ->orWhere('name', $value)
            ->exists();
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'Country ":input" not found in the database.';
    }
}
