##MPs’ Degrees

This PHP script:

- Pulls a list of current MPs from http://www.theyworkforyou.com/
- Looks up the Wikipedia article for each MP
- Searches the article’s text for mention of their university, degree and any other occupation
- Categorises those degrees into one of eighteen groups

See the results of the script here: http://subtleengine.org/2014/05/11/mps-degrees-what-do-they-know/

This is not elegant or particularly reliable code. Pull requests welcome!

Run the script like this to save the output to a file:

``php ./MP_degrees.php > MP_Uni_output.csv``

or use tee to see output while saving to a file:

``php ./MP_degrees.php | tee MP_Uni_output.csv``