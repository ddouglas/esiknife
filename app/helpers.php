<?php

use \Carbon\Carbon;

if (!function_exists('age')) {
    function age(Carbon $start, Carbon $end)
    {
        $difference = $start->diff($end);
        $results = "";

        if ($difference->y != 0) {
            if ($difference->y > 1) {
                $results .= $difference->y." years ";
            } else {
                $results .= $difference->y." year ";
            }
        }
        if ($difference->m != 0) {
            if ($difference->m > 1) {
                $results .= $difference->m." months ";
            } else {
                $results .= $difference->m." month ";
            }
        }
        if ($difference->d != 0) {
            if ($difference->d > 1) {
                $results .= $difference->d." days ";
            } else {
                $results .= $difference->d." day ";
            }
        }
        if ($results === "") {
            if ($difference->h != 0) {
                if ($difference->h > 1) {
                    $results .= $difference->h." hours ";
                } else {
                    $results .= $difference->h." hour ";
                }
            }
            if ($difference->i != 0) {
                if ($difference->i > 1) {
                    $results .= $difference->i." minutes ";
                } else {
                    $results .= $difference->i." minute ";
                }
            }
            if ($difference->s != 0) {
                if ($difference->s > 1) {
                    $results .= $difference->s." seconds ";
                } else {
                    $results .= $difference->s." second ";
                }
            }
        }
        return trim($results);
    }
}
