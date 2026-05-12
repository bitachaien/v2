<?php

namespace app\library;

class WinChecker
{
    
    public static function checkSscDouble($opencode, $playid, $tzcode)
    {
        $nums = explode(',', $opencode);
        
        
        if (strpos($playid, 'lmp_d1q_') === 0) {
            $num = intval($nums[0]);
            if (strpos($playid, 'da') !== false) return $num >= 5;
            if (strpos($playid, 'xiao') !== false) return $num < 5;
            if (strpos($playid, 'dan') !== false) return $num % 2 == 1;
            if (strpos($playid, 'shuang') !== false) return $num % 2 == 0;
        }
        
        
        if (strpos($playid, 'lmp_d2q_') === 0) {
            $num = intval($nums[1]);
            if (strpos($playid, 'da') !== false) return $num >= 5;
            if (strpos($playid, 'xiao') !== false) return $num < 5;
            if (strpos($playid, 'dan') !== false) return $num % 2 == 1;
            if (strpos($playid, 'shuang') !== false) return $num % 2 == 0;
        }
        
        
        if (strpos($playid, 'lmp_d3q_') === 0) {
            $num = intval($nums[2]);
            if (strpos($playid, 'da') !== false) return $num >= 5;
            if (strpos($playid, 'xiao') !== false) return $num < 5;
            if (strpos($playid, 'dan') !== false) return $num % 2 == 1;
            if (strpos($playid, 'shuang') !== false) return $num % 2 == 0;
        }
        
        
        if (strpos($playid, 'lmp_d4q_') === 0) {
            $num = intval($nums[3]);
            if (strpos($playid, 'da') !== false) return $num >= 5;
            if (strpos($playid, 'xiao') !== false) return $num < 5;
            if (strpos($playid, 'dan') !== false) return $num % 2 == 1;
            if (strpos($playid, 'shuang') !== false) return $num % 2 == 0;
        }
        
        
        if (strpos($playid, 'lmp_d5q_') === 0) {
            $num = intval($nums[4]);
            if (strpos($playid, 'da') !== false) return $num >= 5;
            if (strpos($playid, 'xiao') !== false) return $num < 5;
            if (strpos($playid, 'dan') !== false) return $num % 2 == 1;
            if (strpos($playid, 'shuang') !== false) return $num % 2 == 0;
        }
        
        
        if (strpos($playid, 'lmp_zongh_') === 0) {
            $sum = array_sum($nums);
            if (strpos($playid, 'da') !== false) return $sum >= 23;
            if (strpos($playid, 'xiao') !== false) return $sum < 23;
            if (strpos($playid, 'dan') !== false) return $sum % 2 == 1;
            if (strpos($playid, 'shuang') !== false) return $sum % 2 == 0;
        }
        
        
        if (strpos($playid, 'lmp_lh_') === 0) {
            $first = intval($nums[0]);
            $last = intval($nums[4]);
            if (strpos($playid, 'long') !== false) return $first > $last;
            if (strpos($playid, 'hu') !== false) return $first < $last;
            if (strpos($playid, 'he') !== false) return $first == $last;
        }
        
        return false;
    }
    
    
    public static function checkK3Double($opencode, $playid, $tzcode)
    {
        $nums = explode(',', $opencode);
        $sum = array_sum($nums);
        
        
        if (strpos($playid, 'sum_') === 0 || strpos($playid, 'hezhi_') === 0) {
            if (strpos($playid, 'big') !== false || strpos($playid, 'da') !== false) {
                return $sum >= 11; 
            }
            if (strpos($playid, 'small') !== false || strpos($playid, 'xiao') !== false) {
                return $sum <= 10; 
            }
            if (strpos($playid, 'odd') !== false || strpos($playid, 'dan') !== false) {
                return $sum % 2 == 1;
            }
            if (strpos($playid, 'even') !== false || strpos($playid, 'shuang') !== false) {
                return $sum % 2 == 0;
            }
        }
        
        
        if (strpos($playid, 'big_odd') !== false || strpos($playid, 'dadan') !== false) {
            return $sum >= 11 && $sum % 2 == 1;
        }
        if (strpos($playid, 'big_even') !== false || strpos($playid, 'dashuang') !== false) {
            return $sum >= 11 && $sum % 2 == 0;
        }
        if (strpos($playid, 'small_odd') !== false || strpos($playid, 'xiaodan') !== false) {
            return $sum <= 10 && $sum % 2 == 1;
        }
        if (strpos($playid, 'small_even') !== false || strpos($playid, 'xiaoshuang') !== false) {
            return $sum <= 10 && $sum % 2 == 0;
        }
        
        return false;
    }
    
    
    public static function checkPk10Double($opencode, $playid, $tzcode)
    {
        $nums = explode(',', $opencode);
        
        
        if (strpos($playid, 'lmp_d1m_') === 0 || strpos($playid, 'gyj_') === 0) {
            $num = intval($nums[0]);
            if (strpos($playid, 'da') !== false || strpos($playid, 'big') !== false) {
                return $num >= 6;
            }
            if (strpos($playid, 'xiao') !== false || strpos($playid, 'small') !== false) {
                return $num <= 5;
            }
            if (strpos($playid, 'dan') !== false || strpos($playid, 'odd') !== false) {
                return $num % 2 == 1;
            }
            if (strpos($playid, 'shuang') !== false || strpos($playid, 'even') !== false) {
                return $num % 2 == 0;
            }
        }
        
        
        if (strpos($playid, 'lmp_d2m_') === 0 || strpos($playid, 'yj_') === 0) {
            $num = intval($nums[1]);
            if (strpos($playid, 'da') !== false || strpos($playid, 'big') !== false) {
                return $num >= 6;
            }
            if (strpos($playid, 'xiao') !== false || strpos($playid, 'small') !== false) {
                return $num <= 5;
            }
            if (strpos($playid, 'dan') !== false || strpos($playid, 'odd') !== false) {
                return $num % 2 == 1;
            }
            if (strpos($playid, 'shuang') !== false || strpos($playid, 'even') !== false) {
                return $num % 2 == 0;
            }
        }
        
        
        if (strpos($playid, 'gyh_') === 0) {
            $sum = intval($nums[0]) + intval($nums[1]);
            if (strpos($playid, 'da') !== false || strpos($playid, 'big') !== false) {
                return $sum >= 12;
            }
            if (strpos($playid, 'xiao') !== false || strpos($playid, 'small') !== false) {
                return $sum <= 11;
            }
            if (strpos($playid, 'dan') !== false || strpos($playid, 'odd') !== false) {
                return $sum % 2 == 1;
            }
            if (strpos($playid, 'shuang') !== false || strpos($playid, 'even') !== false) {
                return $sum % 2 == 0;
            }
        }
        
        return false;
    }
    
    
    public static function checkX5Double($opencode, $playid, $tzcode)
    {
        $nums = explode(',', $opencode);
        
        
        if (strpos($playid, 'sum_') === 0) {
            $sum = array_sum($nums);
            if (strpos($playid, 'big') !== false || strpos($playid, 'da') !== false) {
                return $sum >= 36; 
            }
            if (strpos($playid, 'small') !== false || strpos($playid, 'xiao') !== false) {
                return $sum <= 35; 
            }
            if (strpos($playid, 'odd') !== false || strpos($playid, 'dan') !== false) {
                return $sum % 2 == 1;
            }
            if (strpos($playid, 'even') !== false || strpos($playid, 'shuang') !== false) {
                return $sum % 2 == 0;
            }
        }
        
        
        if (strpos($playid, 'dragon') !== false || strpos($playid, 'long') !== false) {
            return intval($nums[0]) > intval($nums[4]);
        }
        if (strpos($playid, 'tiger') !== false || strpos($playid, 'hu') !== false) {
            return intval($nums[0]) < intval($nums[4]);
        }
        if (strpos($playid, 'tie') !== false || strpos($playid, 'he') !== false) {
            return intval($nums[0]) == intval($nums[4]);
        }
        
        return false;
    }
    
    
    public static function checkLhcDouble($opencode, $playid, $tzcode)
    {
        $nums = explode(',', $opencode);
        $teMa = intval(end($nums)); 
        
        
        if (strpos($playid, 'tema_daxiao') !== false || strpos($playid, 'tm_dx') !== false) {
            if (strpos($playid, 'da') !== false || strpos($playid, 'big') !== false) {
                return $teMa >= 25;
            }
            if (strpos($playid, 'xiao') !== false || strpos($playid, 'small') !== false) {
                return $teMa < 25;
            }
        }
        
        
        if (strpos($playid, 'tema_danshuang') !== false || strpos($playid, 'tm_ds') !== false) {
            if (strpos($playid, 'dan') !== false || strpos($playid, 'odd') !== false) {
                return $teMa % 2 == 1;
            }
            if (strpos($playid, 'shuang') !== false || strpos($playid, 'even') !== false) {
                return $teMa % 2 == 0;
            }
        }
        
        
        $sum = array_reduce($nums, function($carry, $item) {
            return $carry + intval($item);
        }, 0);
        
        if (strpos($playid, 'zongh_da') !== false) return $sum >= 175;
        if (strpos($playid, 'zongh_xiao') !== false) return $sum < 175;
        if (strpos($playid, 'zongh_dan') !== false) return $sum % 2 == 1;
        if (strpos($playid, 'zongh_shuang') !== false) return $sum % 2 == 0;
        
        return false;
    }
}
