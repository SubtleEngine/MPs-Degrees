#MPs’ Degrees

This PHP script:

- Pulls a list of current MPs from http://www.theyworkforyou.com/
- Looks up the Wikipedia article for each MP
- Searches the article’s text for mention of their university, degree and any other occupation
- Categorises those degrees into one of eighteen groups

Run the script like this:

php ./MP_degrees.php or php ./MP_degrees.php | tee MP_Uni_output.csv if you want to see the (tab seperated) output.