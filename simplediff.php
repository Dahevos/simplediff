<?php

/*
	Paul's Simple Diff Algorithm v 0.1
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
	which can easily be styled with CSS.  
*/

function diff($old, $new){
	$maxlen = 0;
	foreach($old as $oindex => $ovalue){
		$nkeys = array_keys($new, $ovalue);
		foreach($nkeys as $nindex){
			$matrix[$oindex][$nindex] = isset($matrix[$oindex - 1][$nindex - 1]) ?
				$matrix[$oindex - 1][$nindex - 1] + 1 : 1;
			if($matrix[$oindex][$nindex] > $maxlen){
				$maxlen = $matrix[$oindex][$nindex];
				$omax = $oindex + 1 - $maxlen;
				$nmax = $nindex + 1 - $maxlen;
			}
		}	
	}
	if($maxlen == 0) return array(array('d'=>$old, 'i'=>$new));
	return array_merge(
		diff(array_slice($old, 0, $omax), array_slice($new, 0, $nmax)),
		array_slice($new, $nmax, $maxlen),
		diff(array_slice($old, $omax + $maxlen), array_slice($new, $nmax + $maxlen)));
}


/*
    Cette fonction produit une chaine suivant le format : 
    (\d+)(d\([^()]*\))?(i\([^()]*\))?
    example : 
    $old = Salut je suis français
    $new = Bonjour je suis chinois
    produit : 
    0d(Salut)i(Bonjour)3d(français)i(chinois)
    la position est relative à la chaine FINALE (CORRIGE)

    diff renvoie un array avec soit une chaine = pas de modif, soit un array avec la valeur a delete (d) ou/et la valeur à insert (i)
*/
function encodeHtmlDiff($old, $new){

		$diff = diff(explode(' ', $old), explode(' ', $new));
        $modif2save = "";
        $pos = 0;

		foreach($diff as $k){
			if(is_array($k)) {
                if (!empty($k['d']) || !empty($k['i'])) // il y a suppression ou insertion, on écrit la pos
                    $modif2save .= "$pos";
                if (!empty($k['d'])) {
                    $modif2save .= "d(" . implode(' ',$k['d']) . ")"; // on écrit la mot a delete. On increm pas car le mot supprimé sera absent
                                                                      // de la chaine finale, donc il faut pas avancé !
                }
                if (!empty($k['i'])) {
                    $modif2save .= "i(" . implode(' ',$k['i']) . ")"; // on écrit le mot à insert, et on avance
                    $pos += sizeof($k['i']);
                }
                    
            }
            else { 
		        $pos++; // peu importe le mot, on avance
            }
		}
    
		return $modif2save;
}


/**
    Transforme original suivant la valeur encode
    Exemple : 
    $original = Bonjour je suis chinois
    $encode = 0d(Salut)i(Bonjour)3d(français)i(chinois)
    produit : 
    <del>Salut</del><ins>Bonjour</ins> je suis <del>français</del><ins>chinois</ins> 
*/

function decodeHtmlDiff($original, $encode) {

    $newString = explode(" ",$original); /* chaine à modifier */
    preg_match_all('/(\d+)(d\([^()]*\))?(i\([^()]*\))?/', $encode, $data, PREG_SET_ORDER);

    $data = array_reverse($data); // on reverse le tableau, car les insertions/suppression vont modifier la taille de la chaine finale.
                                  // obligé donc de partir de la fin.

    foreach($data as $val) {
        /* on recupère la position et la chaine à inserer */
        $pos = $val[1];
        $toInsert = "";
        $flag = 0;
        if (isset($val[2]) && $val[2] != "" && $val[2][0] == 'd') {
            $toInsert .= "<del>".substr($val[2], 2, strlen($val[2])-3)."</del> ";
            $flag--;
        }
        if (isset($val[3]) && $val[3] != "" && $val[3][0] == 'i') {
            $toInsert .= " <ins>".substr($val[3], 2, strlen($val[3])-3)."</ins>";
            $flag++;
        }
        
        if ($flag == -1) { // juste une suppression
            array_splice ($newString, $pos, 1, explode(" ",$toInsert . $newString[$pos]) );
        } else if ($flag == 0) { // une suppression et une insertion
            array_splice ($newString, $pos, sizeof(explode(" ", $val[3])), explode(" ",$toInsert) );
        }else { // une insertion
            array_splice ($newString, $pos, sizeof(explode(" ", $val[3])), explode(" ", $toInsert));
        }

    } 

    return implode(" ", $newString);

}

?>
