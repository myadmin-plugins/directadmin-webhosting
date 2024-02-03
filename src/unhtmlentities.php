<?php

function unhtmlentities($string)
{
    return preg_replace_callback(
        '~&#([0-9][0-9])~',
        function ($matches) {
            return chr($matches);
        },
        $string
    );
}
