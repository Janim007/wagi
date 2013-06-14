wagi
====

Wagi, Wordpress plugin to show most popular posts from Google analytics


How to install
--------------
download the zip file, extract it
upload with FTP to [your wordpress installation folder]/wp-content/plugins/

go to your wordpress website, plugins page .. activate

then fid in the settings link called    wagi analytics

then go to https://code.google.com/apis/console/ click API Access left menu item, create new project or add support to the current domain in any existing app setup

make sure to add the redirect url as follows:
*http://your-website-domain.com/your-wordpress-installation-folder-if-any/wp-admin/options-general.php?page=wagi_google_api_popular_Posts
