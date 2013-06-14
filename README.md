wagi
====

Wagi, Wordpress plugin to show most popular posts from Google analytics


How to install
--------------
* download the zip file, extract it
* upload with FTP to [your wordpress installation folder]/wp-content/plugins/

* Go to your wordpress website, plugins page .. activate

* find in the settings link called    wagi analytics

* go to https://code.google.com/apis/console/ click API Access left menu item, create new project or add support to the current domain in any existing app setup

make sure to add the redirect url as follows:
*http://your-website-domain.com/your-wordpress-installation-folder-if-any/wp-admin/options-general.php?page=wagi_google_api_popular_Posts

copy/page client Id, client Secret from your google console page, set them in plugin setting, click save credentioals
* now you should see button called Click here to Authorize Google Analyitcs to use this plugin!
* go to your dashboard and have fun