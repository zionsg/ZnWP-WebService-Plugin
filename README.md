## ZnWP WebService Plugin

This WordPress plugin provides a settings page and 2 simple methods for accessing a generic web service.

### Installation
Steps
- Click the "Download Zip" button on the righthand side of this GitHub page.
- Remove the version/branch from the filename, i.e., rename the downloaded zip file to `znwp-webservice-plugin.zip`.
- Do one of the following:
  + Uncompress the zip file to a `znwp-webservice-plugin` and copy it to your WordPress plugins folder,
    i.e. `wp-content/plugins`.
  + Upload the zip file via the WordPress admin interface.
- Uncompress the zip file on your desktop
- Activate the plugin

### Usage
The settings page can be accessed via the Settings link for the plugin on the Plugins page.

To use in your code:
```
global $znwp_webservice_plugin;
$response = $znwp_webservice_plugin->get_response(); // uses values from settings
$body = $znwp_webservice_plugin->get_response_body($response);
```
