# Mobile Cloaker

## Project Requirements

* Apache 2+
* PHP Version 5.4+
* Apache's Mod_rewrite module

## Project Structure

Mobile Cloaker is build on top of the CodeIgniter Framework version 3.1.4, with a few custom modifications:
* Application code at [root/application/](https://github.com/DerpAds/Mobile-cloaker/application/)
* Framework code at [root/system/](https://github.com/DerpAds/Mobile-cloaker/system/)
* Site's entry point at [root/public/index.php](https://github.com/DerpAds/Mobile-cloaker/public/index.php) 
* Ads client side assets like Images, HTML Documents, Script files are located at [root/public/assets](https://github.com/DerpAds/Mobile-cloaker/public/assets) 

Also there's a redirection file from root folder to the public folder's entry point at [root/index.php](https://github.com/DerpAds/Mobile-cloaker/index.php) for site link simplification.

## Configuration

The site is build so minimal configuration is required. To install a new server the project's requirements must be met, and the code should be copied on the desired apache's ********************************public_html******************************** folder. 

So, for example, if the code is copied to ********************************../public_html/adhost********************************, the site's URL should be ********************************http://<server address or domain name>/adhost********************************. That would redirect to the site's entry point located at the url ********************************http://<server address or domain name>/adhost/public******************************** displaying the ads manager login form.

### Setting Ad Manager's users

To add, remove or modify ad manager's users the [security config file](https://github.com/DerpAds/Mobile-cloaker/application/config/security.php) must be modified.

There should be one item on the ********************************$config['users']******************************** array, for each user name, containing the password md5 hash.

````````````````
$config['users']["username1"] = "731161253fafb5236f015e1d5e1c5964";
$config['users']["username2"] = "876d89f7g6d8f75gsd786f4gs76d4f7g";
$config['users']["username3"] = "76df8g7h5fg87h58s7g4g8dfg76fgf5s";
````````````````

### Adding/Modifiying custom assets for the Ads

Inside the HTML document files for each clean ad, there could be references to custom assets hosted on the cloaker's server in the [public assets folder](https://github.com/DerpAds/Mobile-cloaker/public/assets), these files must not be referenced using relative URL paths, but using the {assetsUrl} tag inside their URL.

For example, having the following image on the assets folder

********************************../adhost/public/assets/images/image1.jpg********************************

In the HTML file, it shoud be referenced as

````````````````<img src="{assetsUrl}/images/image1.jpg"/>````````````````

### Ads data files

* Ads config files are located at [application/data/ads/](https://github.com/DerpAds/Mobile-cloaker/application/data/ads)
* Per platform/region .csv config files are located at [application/data/config/](https://github.com/DerpAds/Mobile-cloaker/application/data/config)
* Database .db files are located at [application/data/dbs/](https://github.com/DerpAds/Mobile-cloaker/application/data/dbs)
* Profile templates are located at [application/data/profiles/](https://github.com/DerpAds/Mobile-cloaker/application/data/profiles)
* Log files are located at [application/logs/](https://github.com/DerpAds/Mobile-cloaker/application/data/logs)
