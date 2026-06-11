<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;
use App\Models\Ems\Event;

class EventExists implements Rule
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

        return Event::where('name', $value)->exists();
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'Event ":input" not found in the database.';
    }
}
