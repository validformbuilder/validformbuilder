<?php

class ExternalValidation
{
    /**
     * @param mixed $value
     * @param mixed $arg1
     * @param mixed $arg2
     * @return bool
     */
    public static function validate($value, $arg1, $arg2)
    {
        return $value % 2 === 0;
    }
}
