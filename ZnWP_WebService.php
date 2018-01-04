<?php
/**
 * Plugin class
 *
 * This plugin provides a settings page and 2 simple methods for accessing a generic web service.
 *
 * Usage:
 *   global $znwp_webservice_plugin;
 *   $response = $znwp_webservice_plugin->get_response(); // uses values from settings
 *   $body = $znwp_webservice_plugin->get_response_body($response);
 *
 * @link    For testing, use http://httpbin.org/
 * @package ZnWP WebService Plugin
 * @author  Zion Ng <zion@intzone.com>
 * @link    https://github.com/zionsg/ZnWP-WebService-Plugin for canonical source repository
 */

class ZnWP_WebService
{
    /**
     * Full filename of plugin including directory
     *
     * @var string
     */
    protected $plugin;

    /**
     * Short plugin name for use in slugs
     *
     * @var string
     */
    protected $plugin_name;

    /**
     * Display name for plugin - typically the class name without the underscores
     *
     * @var string
     */
    protected $plugin_display_name;

    /**
     * Options slug used for storing settings
     *
     * @var string
     */
    protected $options_slug;

    /**
     * Form field params for options
     *
     * @example
     *   array('option_name' => array(
     *       'label' => 'My Option',
     *       'type'  => 'select',
     *       'value' => 'a',
     *       'options' => array(
     *           'a' => 'Alpha',
     *           'b' => 'Beta',
     *       ),
     *       'option_separator' => '',
     *   ))
     * @var array
     */
    protected $option_fields = array(
        'url' => array(
            'label' => 'API Url',
            'type' => 'text',
            'size' => 100,
            'value' => 'http://httpbin.org/'
        ),
        'method' => array(
            'label' => 'HTTP Method',
            'type' => 'select',
            'value' => 'GET',
            'options' => array('POST' => 'POST', 'GET' => 'GET'),
        ),
        'content_type' => array(
            'label' => 'Content Type',
            'type' => 'select',
            'value' => 'HTML',
            'options' => array(
                'application/json' => 'JSON',
                'application/xml' => 'XML',
                'text/html' => 'HTML',
            ),
        ),
        'sslverify' => array(
            'label' => 'Verify SSL for HTTPS sites',
            'type' => 'select',
            'value' => 0,
            'options' => array(0 => 'no', 1 => 'yes'),
        ),
        'headers' => array(
            'label' => 'Additional Headers',
            'type' => 'textarea',
            'description' => 'One header per line in the format: header=value',
            'rows' => 5,
            'cols' => 100,
        ),
        'data' => array(
            'label' => 'Additional POST Data',
            'type' => 'textarea',
            'description' => 'One parameter per line in the format: parameter=value',
            'rows' => 5,
            'cols' => 100,
        ),
    );

    /**
     * Defaults for form field params
     *
     * @var array
     */
    protected $option_field_defaults = array(
        'label'       => '',
        'type'        => 'text',
        'description' => '',
        'size'        => '',      // for text
        'rows'        => '',      // for textarea
        'cols'        => '',      // for textarea
        'value'       => '',      // can be array of values if multiple values are selected in a multi-checkbox
        'options'     => array(), // value-option pairs for dropdowns, checkboxes, radio groups
        'option_separator' => '', // separator between options, typically <br> for multi-checkbox
    );

    /**
     * Saved settings
     *
     * @var array
     */
    protected $settings = array();

    /**
     * Headers from settings
     *
     * @var array
     */
    protected $headers = array();

    /**
     * POST data from settings
     *
     * @var array
     */
    protected $data = array();

    /**
     * Constructor
     *
     * @param string $plugin
     */
    public function __construct($plugin)
    {
        $this->plugin = $plugin;
        $this->plugin_name = basename($plugin, '.php');
        $this->plugin_display_name = str_replace('_', ' ', __CLASS__);
        $this->options_slug = $this->plugin_name . '_options';
        $this->settings = get_option($this->options_slug, array());

        // Headers
        $this->headers = array('Content-Type' => $this->settings['content_type']);
        $lines = explode("\n", $this->settings['headers']);
        foreach ($lines as $line) {
            $parts = array_map('trim', explode('=', $line));
            if (count($parts) != 2) {
                continue;
            }
            $this->headers[$parts[0]] = $parts[1];
        }

        // POST data
        $lines = explode("\n", $this->settings['data']);
        foreach ($lines as $line) {
            $parts = array_map('trim', explode('=', $line));
            if (count($parts) != 2) {
                continue;
            }
            $this->data[$parts[0]] = $parts[1];
        }
    }

    /**
     * Get response from web service
     *
     * To override or add to POST data, use $args['body'].
     *
     * @link   https://johnblackbourn.com/wordpress-http-api-basicauth if Basic Authentication is involved
     * @link   http://codex.wordpress.org/Function_Reference/wp_remote_post
     * @param  string $url  Optional url to use instead of the API Url in Settings
     * @param  array  $args Optional arguments. @link http://codex.wordpress.org/Function_Reference/wp_remote_get
     * @return WP_Error|array
     */
    public function get_response($url = null, array $args = array())
    {
        $settings = $this->settings;

        return wp_remote_request(
            $url ?: $settings['url'],
            array_merge(
                array(
                    'method'    => $settings['method'],
                    'headers'   => $this->headers,
                    'body'      => $this->data,
                    'sslverify' => ($settings['sslverify'] ? true : false),
                ),
                $args
            )
        );
    }

    /**
     * Get body from raw response
     *
     * @link   http://codex.wordpress.org/HTTP_API for helper functions to retrieve the other parts from the response
     * @param  WP_Error|array $response Result from get_response()
     * @return string @link http://codex.wordpress.org/Function_Reference/wp_remote_retrieve_body
     */
    public function get_response_body($response)
    {
        return wp_remote_retrieve_body($response);
    }

    /**
     * Plugin initialization
     *
     * @return void
     */
    public function init()
    {
        // Add Settings link for plugin on Plugins page
        add_filter("plugin_action_links_{$this->plugin}", function ($links) {
            $settings_link = "<a href=\"options-general.php?page={$this->options_slug}\">Settings</a>";
            array_unshift($links, $settings_link);

            return $links;
        });

        // Init plugin options and add settings page
        add_action('admin_init', array($this, 'admin_init'));
        add_action('admin_menu', array($this, 'admin_menu'));
    }

    /**
     * Admin init callback
     *
     * @return void
     */
    public function admin_init()
    {
        register_setting($this->options_slug, $this->options_slug, array($this, 'validate_options'));
    }

    /**
     * Admin menu callback
     *
     * @return void
     */
    public function admin_menu()
    {
        add_options_page(
            $this->plugin_display_name,
            $this->plugin_display_name,
            'manage_options',
            $this->options_slug,
            array($this, 'options_page')
        );
    }

    /**
     * Options page
     */
    public function options_page()
    {
        $settings = $this->settings;

        // Page start
        printf(
            PHP_EOL . '
            <div class="wrap">
              <h2>%s Settings</h2>
              <form method="post" action="options.php">' . PHP_EOL,
            $this->plugin_display_name
        );
        settings_fields($this->options_slug);
        echo PHP_EOL, '                <table class="form-table">', PHP_EOL;

        // Input fields for options
        foreach ($this->option_fields as $option_name => $field) {
            $field = array_merge($this->option_field_defaults, $field);
            $type  = $field['type'];
            $value = isset($settings[$option_name]) ? $settings[$option_name] : $field['value'];
            $html  = '';

            if ('select' == $type) {
                $html .= sprintf('<select name="%s[%s]">', $this->options_slug, $option_name);
                $value = is_array($value) ? $value : array($value);

                foreach ($field['options'] as $list_value => $list_label) {
                    $html .= sprintf(
                        '<option value="%s" %s>%s</option>',
                        $list_value,
                        in_array($list_value, $value) ? 'selected="selected"' : '',
                        $list_label
                    );
                }

                $html .= '</select>';
            } elseif ('checkbox' == $type || 'radio' == $type) {
                $value = is_array($value) ? $value : array($value);

                foreach ($field['options'] as $list_value => $list_label) {
                    $html .= sprintf(
                        '<input name="%s[%s]%s" type="%s" value="%s" %s />%s%s',
                        $this->options_slug,
                        $option_name,
                        ('checkbox' == $type) ? '[]' : '',  // note the [] for name
                        $type,
                        $list_value,
                        in_array($list_value, $value) ? 'checked="checked"' : '',
                        $list_label,
                        $field['option_separator']
                    );
                }
            } elseif ('textarea' == $type) {
                $html .= sprintf(
                    '<textarea name="%s[%s]" rows="%d" cols="%d">%s</textarea>',
                    $this->options_slug,
                    $option_name,
                    $field['rows'],
                    $field['cols'],
                    $value
                );
            } else {
                $html .= sprintf(
                    '<input name="%s[%s]" type="%s" value="%s" size="%d" />',
                    $this->options_slug,
                    $option_name,
                    $type,
                    $value,
                    $field['size']
                );
            }

            printf(
                PHP_EOL . '<tr valign="top"><th scope="row">%s</th><td>%s%s</td></tr>' . PHP_EOL,
                $field['label'],
                $html,
                ($field['description'] ? "<div><em>{$field['description']}</em></div>" : '')
            );
        }

        // Page end
        echo '
                </table>
                <p class="submit"><input type="submit" class="button-primary" value="Save" /></p>
              </form>
            </div>
        ';
    }

    /**
     * Sanitize and validate options
     *
     * @param  array $input
     * @return array
     */
    public function validate_options($input)
    {
        return $input;
    }
}
