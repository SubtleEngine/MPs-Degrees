<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

$mp_list = fopen("http://www.theyworkforyou.com/mps/?f=csv", 'r');
$wiki_base_url = "http://en.wikipedia.org/w/api.php?action=parse&prop=text&format=json&page=";

$subject_areas = array ( //Based on HESA subject area groups: http://www.hesa.ac.uk/dox/dataTables/studentsAndQualifiers/download/Subject1213.xlsx
    "Medicine & dentistry" => 0,
    "Subjects allied to medicine" => 0,
    "Biological sciences" => 0,
    "Veterinary sciences" => 0,
    "Agriculture & related subjects" => 0,
    "Physical sciences" => 0,
    "Mathematical sciences" => 0,
    "Computer sciences" => 0,
    "Engineering & technology" => 0,
    "Architecture, building & planning" => 0,
    "Social studies" => 0,
    "Law" => 0,
    "Business & administrative studies" => 0,
    "Mass communications & documentation" => 0,
    "Languages" => 0,
    "Historical & philosophical studies" => 0,
    "Creative arts & design" => 0,
    "Education" => 0
);

//Willie_Bain
//Geraint_Davies_(Labour_politician)
//Geoffrey_Clifton-Brown_(born_1953)
//Siân_James_(politician)
//Julian_Lewis_(MP)
//Mike_Weir_(politician)
//Gareth_Thomas_(English_politician)

while (($mp = fgetcsv($mp_list, 1000, ",")) !== FALSE) {
    if ($mp[1] != "First name") { //Ignore the header row
    
        $first_name = $mp[1];
        $last_name = str_replace(" ", "_", $mp[2]); //Replace any spaces with underscores e.g. for Nick_de_Bois
        
        $subject_area = "";
        
        $wiki = json_decode(file_get_contents($wiki_base_url.$first_name."_".$last_name)); //Fetch the Wiki article
        $content = strip_tags($wiki->{'parse'}->{'text'}->{'*'}); //Retrieve text and strip HTML tags
        
        if (strpos($content, "may refer to:") || strpos($content, "The page you specified doesn't exist")) { //If a disambiguation page, try appending _(Politician)
            $wiki = json_decode(file_get_contents($wiki_base_url.$first_name."_".$last_name."_(politician)")); //Fetch the Wiki article
            if (!isset($wiki->{'error'})) { //If no page, API returns error – only strip tags if _(Politician) page worked
                $content = strip_tags($wiki->{'parse'}->{'text'}->{'*'}); //Retrieve text and strip HTML tags
            }
        }
        
        if (strpos($content, "may refer to:") || strpos($content, "The page you specified doesn't exist")) {
            $wiki = json_decode(file_get_contents($wiki_base_url.$first_name."_".$last_name."_(British_politician)")); //Fetch the Wiki article
            if (!isset($wiki->{'error'})) { //If no page, API returns error – only strip tags if _(British_politician) page worked
                $content = strip_tags($wiki->{'parse'}->{'text'}->{'*'}); //Retrieve text and strip HTML tags
            }
        }
        
        //Parse text from Wiki article
        
        $uni_pattern = "/(Alma mater)\n(.*)\n/m"; //Search for MP's university in side panel
        preg_match($uni_pattern, $content, $uni_matches);
        
        $occupation_pattern = "/(Occupation|Profession)\n(.*)\n/m"; //Search for MP's occupation in side panel
        preg_match($occupation_pattern, $content, $occupation_matches);

        $content = str_replace("[", " ", $content);

        $degree_pattern = "/\s(read|reading|study|studied|studying|degree in|graduating|earned a|earned an|earning|obtained a|obtained an|graduated|qualified|received a|gained a|gaining a|awarded|first in)\s([a-zA-Z\,\s]*)/m"; //Search for degree
        preg_match_all($degree_pattern, $content, $degree_matches);
        
        //Categorise degrees by subject area
        
        if (isset($degree_matches[2])) {
        
            if (preg_match("/medicine|medical|doctor/im", implode(", ",$degree_matches[2]))) {
                ++$subject_areas["Medicine & dentistry"];
                $subject_area = "Medicine & dentistry";
            }

            elseif (preg_match("/medicine|physiolog|dentist/im", implode(", ",$degree_matches[2]))) {
                ++$subject_areas["Subjects allied to medicine"];
                $subject_area = "Subjects allied to medicine";
            }
            
            elseif (preg_match("/biological/im", implode(", ",$degree_matches[2]))) {
                ++$subject_areas["Biological sciences"];
                $subject_area = "Biological sciences";
            }

            /*elseif (preg_match("//im", implode(", ",$degree_matches[2]))) {
                ++$subject_areas["Veterinary sciences"];
                $subject_area = "Veterinary sciences";
            }*/

            elseif (preg_match("/agricultur|land/im", implode(", ",$degree_matches[2]))) {
                ++$subject_areas["Agriculture & related subjects"];
                $subject_area = "Agriculture & related subjects";
            }
            
            elseif (preg_match("/natural sciences|chemist|physic/im", implode(", ",$degree_matches[2]))) {
                ++$subject_areas["Physical sciences"];
                $subject_area = "Physical sciences";
            }

            elseif (preg_match("/mathematics|math/im", implode(", ",$degree_matches[2]))) {
                ++$subject_areas["Mathematical sciences"];
                $subject_area = "Mathematical sciences";
            }

            elseif (preg_match("/computer/im", implode(", ",$degree_matches[2]))) {
                ++$subject_areas["Computer sciences"];
                $subject_area = "Computer sciences";
            }

            elseif (preg_match("/engineering/im", implode(", ",$degree_matches[2]))) {
                ++$subject_areas["Engineering & technology"];
                $subject_area = "Engineering & technology";
            }

            elseif (preg_match("/architect/im", implode(", ",$degree_matches[2]))) {
                ++$subject_areas["Architecture, building & planning"];
            }

            elseif (preg_match("/economics|ppe|politics|political|social|sociology|geograph|government|human|psych/im", implode(", ",$degree_matches[2]))) {
                ++$subject_areas["Social studies"];
                $subject_area = "Social studies";
            }
            
            elseif (preg_match("/law|llb|llm|solicitor|legal|justice|policy|jurisprudence/im", implode(", ",$degree_matches[2]))) {
                ++$subject_areas["Law"];
                $subject_area = "Law";
            }

            elseif (preg_match("/business|accountan|management/im", implode(", ",$degree_matches[2]))) {
                ++$subject_areas["Business & administrative studies"];
                $subject_area = "Business & administrative studies";
            }

            elseif (preg_match("/communication|journalism/im", implode(", ",$degree_matches[2]))) {
                ++$subject_areas["Mass communications & documentation"];
                $subject_area = "Mass communications & documentation";
            }

            elseif (preg_match("/languages|german|french|english|russia|serbo|literature|litt/im", implode(", ",$degree_matches[2]))) {
                ++$subject_areas["Languages"];
                $subject_area = "Languages";
            }
            
            elseif (preg_match("/histor|philosophy|classic/im", implode(", ",$degree_matches[2]))) {
                ++$subject_areas["Historical & philosophical studies"];
                $subject_area = "Historical & philosophical studies";
            }
            
            elseif (preg_match("/music/im", implode(", ",$degree_matches[2]))) {
                ++$subject_areas["Creative arts & design"];
                $subject_area = "Creative arts & design";
            }
            
            elseif (preg_match("/education/im", implode(", ",$degree_matches[2]))) {
                ++$subject_areas["Education"];
                $subject_area = "Education";
            }
            
        }

        echo "$mp[1]\t$mp[2]"; //Output name

        if (isset($uni_matches[2])) {
            echo "\t".$uni_matches[2]; //Output university if found
        } else {
            echo "\tNo university found";
        }
        
        if (isset($degree_matches[2])) {
            echo "\t".implode(", ", $degree_matches[2]); //Ouput degree if found
        } else {
            echo "\tNo degree found";
        }
        
        if (isset($occupation_matches[2])) {
            echo "\t".$occupation_matches[2]; //Output occupation if found
        } else {
            echo "\tNo occupation found";
        }
        
        if (isset($subject_area)) {
            echo "\t".$subject_area; //Output subject area if found
        } else {
            echo "\tNo category found";
        }
        
        echo "\n";
 
    }
    
}

echo "\n\n";

foreach ($subject_areas as $key => $value) {
    echo "$key\t$value\n";
}

?>