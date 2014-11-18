<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

$mp_list = fopen("http://www.theyworkforyou.com/peers/?f=csv", 'r');
$wiki_base_url = "http://en.wikipedia.org/w/api.php?action=parse&prop=text&format=json&page=";

$tallies = array ( //Based on parties in They Work for You's .csv
    "Bishop" => 0,
    "Conservative" => 0,
    "Crossbench" => 0,
    "DUP" => 0,
    "Green" => 0,
    "Independent Labour" => 0,
    "Judge" => 0,
    "Labour" => 0,
    "LD" => 0,
    "Liberal Democrat" => 0,
    "Non-affiliated" => 0,
    "Other" => 0,
    "Plaid Cymru" => 0,
    "UKIP" => 0,
    "UUP" => 0
);

$subject_areas = array ( //Based on HESA subject area groups: http://www.hesa.ac.uk/dox/dataTables/studentsAndQualifiers/download/Subject1213.xlsx
    "Medicine & dentistry" => $tallies,
    "Subjects allied to medicine" => $tallies,
    "Biological sciences" => $tallies,
    "Veterinary sciences" => $tallies,
    "Agriculture & related subjects" => $tallies,
    "Physical sciences" => $tallies,
    "Mathematical sciences" => $tallies,
    "Computer sciences" => $tallies,
    "Engineering & technology" => $tallies,
    "Architecture, building & planning" => $tallies,
    "Social studies" => $tallies,
    "Law" => $tallies,
    "Business & administrative studies" => $tallies,
    "Mass communications & documentation" => $tallies,
    "Languages" => $tallies,
    "Historical & philosophical studies" => $tallies,
    "Creative arts & design" => $tallies,
    "Education" => $tallies,
    "Other" => $tallies
);

//Known MPs whose Wiki page has an unusual suffix; script currently ignores
//Willie_Bain
//Geraint_Davies_(Labour_politician)
//Geoffrey_Clifton-Brown_(born_1953)
//Siân_James_(politician)
//Julian_Lewis_(MP)
//Mike_Weir_(politician)
//Gareth_Thomas_(English_politician)

echo "Name\tParty\tUniversity\tSubject area\tPhrase\tOccupation\n"; //Header row

while (($mp = fgetcsv($mp_list, 1000, ",")) !== FALSE) {

    if ($mp[1] != "Name") { //Ignore the header row
    
        $first_name = str_replace(" ", "_", $mp[1]); //Replace any spaces with underscores
        //$last_name = str_replace(" ", "_", $mp[2]); //Replace any spaces with underscores e.g. for Nick_de_Bois
        
        $subject_area = "";
        unset($degree_matches);
        $content = "";
        unset($content);
        
        $wiki = json_decode(file_get_contents($wiki_base_url.$first_name)); //Fetch the Wiki article
        if (!isset($wiki->{'error'})) { //If no page, API returns error
            $content = strip_tags($wiki->{'parse'}->{'text'}->{'*'}); //Retrieve text and strip HTML tags
        }
        
        /*if (strpos($content, "may refer to:") || strpos($content, "The page you specified doesn't exist")) { //If a disambiguation page, try appending _(Politician)
            $wiki = json_decode(file_get_contents($wiki_base_url.$first_name."_".$last_name."_(politician)")); //Fetch the Wiki article
            if (!isset($wiki->{'error'})) { //If no page, API returns error – only strip tags if _(Politician) page worked
                $content = strip_tags($wiki->{'parse'}->{'text'}->{'*'}); //Retrieve text and strip HTML tags
            }
        }*/
        
        /*if (strpos($content, "may refer to:") || strpos($content, "The page you specified doesn't exist")) {
            $wiki = json_decode(file_get_contents($wiki_base_url.$first_name."_".$last_name."_(British_politician)")); //Fetch the Wiki article
            if (!isset($wiki->{'error'})) { //If no page, API returns error – only strip tags if _(British_politician) page worked
                $content = strip_tags($wiki->{'parse'}->{'text'}->{'*'}); //Retrieve text and strip HTML tags
            }
        }*/
        
        if (isset($content)) {
            if (preg_match("/(Redirect to:)(.*)/m", $content, $redirect_url)) {
                $wiki = json_decode(file_get_contents($wiki_base_url.str_replace(" ", "_",$redirect_url[2]))); //Fetch the Wiki article
                if (!isset($wiki->{'error'})) { //If no page, API returns error
                    $content = strip_tags($wiki->{'parse'}->{'text'}->{'*'}); //Retrieve text and strip HTML tags
                }
            }
        }
        
        if (isset($content)) {
        
            //Parse text from Wiki article
            
            $uni_pattern = "/(Alma mater)\n(.*)\n/m"; //Search for MP's university in side panel
            preg_match($uni_pattern, $content, $uni_matches);
            
            $occupation_pattern = "/(Occupation|Profession)\n(.*)\n/m"; //Search for MP's occupation in side panel
            preg_match($occupation_pattern, $content, $occupation_matches);

            $content = str_replace("[", " ", $content);

            $degree_pattern = "/\s(read|reading|study|studied|studying|(?<!honorary )degree in|graduating|earned a|earned an|earning|obtained a|obtained an|graduated|qualified|received a|gained a|gaining a|awarded|first in)\s([a-zA-Z\,\s]*)/m"; //Search for degree
            preg_match_all($degree_pattern, $content, $degree_matches);
            
            //Categorise degrees by subject area
            
            //print_r($degree_matches);
            
            if (isset($degree_matches[2]) && !empty($degree_matches[2]) && is_array($degree_matches[2])) {
            
                if (preg_match("/medicine|medical|(?<!honorary )doctor(?! of)/im", implode(", ",$degree_matches[2]))) {
                    ++$subject_areas["Medicine & dentistry"][$mp[2]];
                    $subject_area = "Medicine & dentistry";
                }

                elseif (preg_match("/medicine|physiolog|dentist|radiog|nurs|pharm|nutrit/im", implode(", ",$degree_matches[2]))) {
                    ++$subject_areas["Subjects allied to medicine"][$mp[2]];
                    $subject_area = "Subjects allied to medicine";
                }
                
                elseif (preg_match("/biolog/im", implode(", ",$degree_matches[2]))) {
                    ++$subject_areas["Biological sciences"][$mp[2]];
                    $subject_area = "Biological sciences";
                }

                /*elseif (preg_match("//im", implode(", ",$degree_matches[2]))) { //To do
                    ++$subject_areas["Veterinary sciences"][$mp[2]];
                    $subject_area = "Veterinary sciences";
                }*/

                elseif (preg_match("/agricultur|land man/im", implode(", ",$degree_matches[2]))) {
                    ++$subject_areas["Agriculture & related subjects"][$mp[2]];
                    $subject_area = "Agriculture & related subjects";
                }
                
                elseif (preg_match("/natural sciences|chemist|physic|bacteriolog/im", implode(", ",$degree_matches[2]))) {
                    ++$subject_areas["Physical sciences"][$mp[2]];
                    $subject_area = "Physical sciences";
                }

                elseif (preg_match("/mathematics|math/im", implode(", ",$degree_matches[2]))) {
                    ++$subject_areas["Mathematical sciences"][$mp[2]];
                    $subject_area = "Mathematical sciences";
                }

                elseif (preg_match("/computer/im", implode(", ",$degree_matches[2]))) {
                    ++$subject_areas["Computer sciences"][$mp[2]];
                    $subject_area = "Computer sciences";
                }

                elseif (preg_match("/engineering|mechanic|metallurg/im", implode(", ",$degree_matches[2]))) {
                    ++$subject_areas["Engineering & technology"][$mp[2]];
                    $subject_area = "Engineering & technology";
                }

                elseif (preg_match("/architect/im", implode(", ",$degree_matches[2]))) {
                    ++$subject_areas["Architecture, building & planning"][$mp[2]];
                    $subject_area = "Architecture, building & planning";
                }

                elseif (preg_match("/economics|ppe|politics|political|social|sociology|geograph|government|human|psych|public admin|relations|greats/im", implode(", ",$degree_matches[2]))) {
                    ++$subject_areas["Social studies"][$mp[2]];
                    $subject_area = "Social studies";
                }
                
                elseif (preg_match("/law|llb|llm|solicitor|legal|justice|policy|jurisprudence|barrister/im", implode(", ",$degree_matches[2]))) {
                    ++$subject_areas["Law"][$mp[2]];
                    $subject_area = "Law";
                }

                elseif (preg_match("/business|accountan|management/im", implode(", ",$degree_matches[2]))) {
                    ++$subject_areas["Business & administrative studies"][$mp[2]];
                    $subject_area = "Business & administrative studies";
                }

                elseif (preg_match("/communication|journalism/im", implode(", ",$degree_matches[2]))) {
                    ++$subject_areas["Mass communications & documentation"][$mp[2]];
                    $subject_area = "Mass communications & documentation";
                }

                elseif (preg_match("/languages|german|french|english|russia|serbo|literature|chin|litt/im", implode(", ",$degree_matches[2]))) {
                    ++$subject_areas["Languages"][$mp[2]];
                    $subject_area = "Languages";
                }
                
                elseif (preg_match("/histor|philosophy|theolog|classic/im", implode(", ",$degree_matches[2]))) {
                    ++$subject_areas["Historical & philosophical studies"][$mp[2]];
                    $subject_area = "Historical & philosophical studies";
                }
                
                elseif (preg_match("/music|cultural/im", implode(", ",$degree_matches[2]))) {
                    ++$subject_areas["Creative arts & design"][$mp[2]];
                    $subject_area = "Creative arts & design";
                }
                
                elseif (preg_match("/education/im", implode(", ",$degree_matches[2]))) {
                    ++$subject_areas["Education"][$mp[2]];
                    $subject_area = "Education";
                }
                
                else {
                    ++$subject_areas["Other"][$mp[2]];
                    $subject_area = "Other";
                    $other[$mp[1]] = $degree_matches[2];
                }
                
            }

        }

        echo "$mp[1]\t$mp[2]"; //Output name

        if (isset($uni_matches[2])) {
            echo "\t".$uni_matches[2]; //Output university if found
        } else {
            echo "\tNo university found";
        }
        
        if (isset($subject_area)) {
            echo "\t".$subject_area; //Output subject area if found
        } else {
            echo "\tNo category found";
        }
        
        if (isset($degree_matches[2])) {
            $degree_matches[2] = str_replace("\n", "", $degree_matches[2]); //Strip newlines
            echo "\t".implode(", ", $degree_matches[2]); //Ouput degree if found
        } else {
            echo "\tNo phrase found";
        }
        
        if (isset($occupation_matches[2])) {
            echo "\t".$occupation_matches[2]; //Output occupation if found
        } else {
            echo "\tNo occupation found";
        }
        
        echo "\n";
 
    }
    
}

//Output the results by party

 //Uncomment this section for results table by party

echo "\n\n";

echo "Subject Area\t";

foreach ($tallies as $key => $value) {
    echo "$key\t"; //Header row of parties
}

echo "\n";

foreach ($subject_areas as $key => $value) {
    echo "$key\t"; //Subject area header
    foreach ($value as $k => $v) {
        echo "$v\t"; //Each party's tally
    }
    echo "\n";
}

echo "\n\n";

foreach ($other as $key => $value) {
    //echo "$key\t".implode(";",$value)."\n"; //For checking uncategorised degrees
}

?>