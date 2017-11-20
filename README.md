## ZnWP WebService Plugin

This WordPress plugin provides a settings page and 2 simple methods for accessing a generic web service.

### Installation
Steps
  - Click the "Download Zip" button on the righthand side of this GitHub page
  - Uncompress the zip file on your desktop
  - Copy the `znwp_webservice_plugin` folder to your WordPress plugins folder
    OR compress that folder and upload via the WordPress admin interface
  - Activate the plugin

### Usage
The settings page can be accessed via the Settings link for the plugin on the Plugins page.

To use in your code:
```
global $znwp_webservice_plugin;
$response = $znwp_webservice_plugin->get_response(); // uses values from settings
$body = $znwp_webservice_plugin->get_response_body($response);
```
