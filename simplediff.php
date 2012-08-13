<?php

/* Paul's Simple Diff Algorithm v 0.1
(C) Paul Butler 2007 <http://www.paulbutler.org/>
May be used and distributed under the zlib/libpng license.
This code is intended for learning purposes; it was written with short
code taking priority over performance. It could be used in a practical
application, but there are a few ways it could be optimized.
Given two arrays, the function diff will return an array of the changes.
I won't describe the format of the array, but it will be obvious
if you use print_r() on the result of a diff on some test data.
htmlDiff is a wrapper for the diff command, it takes two strings and
returns the differences in HTML. The tags used are <ins> and <del>,
which can easily be styled with CSS. */	



/*
* diff : compute an array with the difference between two text
*
* var  : $old represent the original sentence
*        $new represent the new sentence
*
* return : an array with the modification
*
*/

function diff($old, $new){
		$matrix = array();
		$maxlen = 0;
		foreach($old as $oindex => $ovalue){
			$nkeys = array_keys($new, $ovalue);
			foreach($nkeys as $nindex){
				$matrix[$oindex][$nindex] = isset($matrix[$oindex - 1][$nindex - 1]) ? $matrix[$oindex - 1][$nindex - 1] + 1 : 1;
				
				if($matrix[$oindex][$nindex] > $maxlen){
					$maxlen = $matrix[$oindex][$nindex];
					$omax = $oindex + 1 - $maxlen;
					$nmax = $nindex + 1 - $maxlen;
				}

			}
            		unset($matrix[$oindex - 1]);

		}
        	unset($matrix);
        	unset($nkeys);

		
		if($maxlen == 0)
            return array(array('d'=>$old, 'i'=>$new));
       

		return array_merge(diff(array_slice($old, 0, $omax), array_slice($new, 0, $nmax)),array_slice($new, $nmax, $maxlen),diff(array_slice($old, $omax + $maxlen), array_slice($new, $nmax + $maxlen)));

	}



/*
* htmlDiff : compute a string with html style to show the differences between two sentence
*
* var  : $old represent the original sentence
*        $new represent the new sentence
*
* return : a string modified with css style
*
*/

function htmlDiff($old, $new){


		$ret = '';
		$diff = diff(explode(' ', $old), explode(' ', $new));
		foreach($diff as $k){
			
			if(is_array($k))
                $ret .= (!empty($k['d'])?"<del STYLE=\"background-color:#FF6347;\" >".implode(' ',$k['d'])."</del> " : '') . (!empty($k['i'])?"<ins STYLE=\"background-color:#9ACD32;\">".implode(' ',$k['i'])."</ins> ":
			''); 
            else 
                $ret .= $k . ' ';
		}

		return $ret;
	}





/*
* patch_make : compute a string - a patch - to apply in order to retrieve the new sentence from the old one.
*
* var  : $old represent the original sentence
*        $new represent the new sentence
*
* return : a string which follow the following regex : (\d+)(d\([^()]*\))?(i\([^()]*\))?
*
* example : 
*    $old = Salut je suis français
*    $new = Bonjour je suis chinois
*    produce : 
*    0d(Salut)i(Bonjour)3d(français)i(chinois)
*
*/
function patch_make($old, $new){

		$diff = diff(explode(' ', $old), explode(' ', $new));
        $modif2save = "";
        $pos = 0;

		foreach($diff as $k){
			if(is_array($k)) {
                if (!empty($k['d']) || !empty($k['i'])) // we have deletion or insertion : we write the pos
                    $modif2save .= "$pos";
                if (!empty($k['d'])) {
                    $modif2save .= "d(" . implode(' ',$k['d']) . ")"; // a deletion : we have to increase the position
                    $pos += sizeof($k['d']);
                }
                if (!empty($k['i'])) {
                    $modif2save .= "i(" . implode(' ',$k['i']) . ")"; // an insertion : do not need to increase because old string does not have
                                                                      // the inserted value
                }
                    
            }
            else { 
		        $pos++; // no insertion, no deletion, we increase pos
            }
		}
    
		return $modif2save;
}




/*
* patch_apply : apply a patch to an old sentence in order to retrieve the new one
*
* var  : $patch represent the patch
*        $original represent the old sentence
*
* return : a string - the new sentence retrieved
*
* example : 
*    $patch = 0d(Salut)i(Bonjour)3d(français)i(chinois)
*    $original = Salut je suis français
*    produce : 
*    Bonjour je suis chinois
*
*/
function patch_apply($patch, $original) {

    $newString = explode(" ",$original); /* chaine à modifier */
    $newString = array_values(array_filter($newString));
    preg_match_all('/(\d+)(d\([^()]*\))?(i\([^()]*\))?/', $patch, $data, PREG_SET_ORDER);

    $data = array_reverse($data); // on reverse le tableau, car les insertions/suppression vont modifier la taille de la chaine finale.
                                  // obligé donc de partir de la fin.

    foreach($data as $val) {
        /* on recupère la position et la chaine à inserer */
        $pos = $val[1];
        if (isset($val[2]) && $val[2] != "" && $val[2][0] == 'd') {
            for($i=$pos; $i < $pos+ sizeof(explode(" ",$val[2])); $i++)
                $newString[$i] = "";
        }
        if (isset($val[3]) && $val[3] != "" && $val[3][0] == 'i') {
            if (isset($newString[$pos]))
                $newString[$pos] = substr($val[3], 2, strlen($val[3])-3) . " " . $newString[$pos];
            else
                array_push($newString, substr($val[3], 2, strlen($val[3])-3));
        }
    } 

    return implode(" ", $newString);

}



?>