<?php
namespace Opencart\Admin\Controller\Extension\PsProductCategoryFilter\Module;
/**
 * Class PsProductCategoryFilter
 *
 * @package Opencart\Admin\Controller\Extension\PsProductCategoryFilter\Module
 */
class PsProductCategoryFilter extends \Opencart\System\Engine\Controller
{
    /**
     * @var string The support email address.
     */
    const EXTENSION_EMAIL = 'support@playfulsparkle.com';

    /**
     * @var string The documentation URL for the extension.
     */
    const EXTENSION_DOC = 'https://github.com/playfulsparkle/oc4_product_category_filter.git';

    /**
     * @return void
     */
    public function index(): void
    {
        $this->load->language('extension/ps_product_category_filter/module/ps_product_category_filter');

        $this->document->setTitle($this->language->get('heading_title'));

        $data['breadcrumbs'] = [];

        $data['breadcrumbs'][] = [
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true),
        ];

        $data['breadcrumbs'][] = [
            'text' => $this->language->get('text_extension'),
            'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true),
        ];

        $data['breadcrumbs'][] = [
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('extension/module/ps_product_category_filter', 'user_token=' . $this->session->data['user_token'], true),
        ];


        $separator = version_compare(VERSION, '4.0.2.0', '>=') ? '.' : '|';

        $data['action'] = $this->url->link('extension/ps_product_category_filter/module/ps_product_category_filter' . $separator . 'save', 'user_token=' . $this->session->data['user_token']);
        $data['back'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module');

        $data['module_ps_product_category_filter_status'] = (bool) $this->config->get('module_ps_product_category_filter_status');

        $data['text_contact'] = sprintf($this->language->get('text_contact'), self::EXTENSION_EMAIL, self::EXTENSION_EMAIL, self::EXTENSION_DOC);

        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('extension/ps_product_category_filter/module/ps_product_category_filter', $data));
    }

    public function save(): void
    {
        $this->load->language('extension/ps_product_category_filter/module/ps_product_category_filter');

        $json = [];

        if (!$this->user->hasPermission('modify', 'extension/ps_product_category_filter/module/ps_product_category_filter')) {
            $json['error'] = $this->language->get('error_permission');
        }

        if (!$json) {
            $this->load->model('setting/setting');

            $this->model_setting_setting->editSetting('module_ps_product_category_filter', $this->request->post);

            $json['success'] = $this->language->get('text_success');
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    public function install(): void
    {
        $this->load->model('setting/event');

        $this->registerEvents();

        $this->load->model('extension/ps_product_category_filter/module/ps_product_category_filter');

        $this->model_extension_ps_product_category_filter_module_ps_product_category_filter->install();
    }

    public function uninstall(): void
    {
        $this->load->model('setting/event');

        $this->unregisterEvents();

        $this->load->model('extension/ps_product_category_filter/module/ps_product_category_filter');

        $this->model_extension_ps_product_category_filter_module_ps_product_category_filter->uninstall();
    }

    private function unregisterEvents(): void
    {
        $this->model_setting_event->deleteEventByCode('module_ps_product_category_filter');
    }

    private function registerEvents(): int
    {
        $separator = version_compare(VERSION, '4.0.2.0', '>=') ? '.' : '|';

        if (version_compare(VERSION, '4.0.1.0', '>=')) {
            $result = $this->model_setting_event->addEvent([
                'code' => 'module_ps_product_category_filter',
                'description' => '',
                'trigger' => 'admin/view/catalog/category_form/before',
                'action' => 'extension/ps_product_category_filter/module/ps_product_category_filter' . $separator . 'eventAdminViewCatalogCategoryFormBefore',
                'status' => '1',
                'sort_order' => '0'
            ]);

            $result = $this->model_setting_event->addEvent([
                'code' => 'module_ps_product_category_filter',
                'description' => '',
                'trigger' => 'admin/model/catalog/product' . $separator . 'editProduct/after',
                'action' => 'extension/ps_product_category_filter/module/ps_product_category_filter' . $separator . 'eventAdminModelCatalogProductEditProductAfter',
                'status' => '1',
                'sort_order' => '0'
            ]);
        } else {
            $result = $this->model_setting_event->addEvent(
                'module_ps_product_category_filter',
                '',
                'admin/view/catalog/category_form/before',
                'extension/ps_product_category_filter/module/ps_product_category_filter' . $separator . 'eventAdminViewCatalogCategoryFormBefore'
            );

            $result = $this->model_setting_event->addEvent(
                'module_ps_product_category_filter',
                '',
                'admin/model/catalog/product' . $separator . 'editProduct/after',
                'extension/ps_product_category_filter/module/ps_product_category_filter' . $separator . 'eventAdminModelCatalogProductEditProductAfter'
            );
        }

        return $result;
    }

    public function eventAdminModelCatalogProductEditProductAfter(string &$route, array &$args): void
    {
        if (!$this->config->get('module_ps_product_category_filter_status')) {
            return;
        }

        $data = [];

        if (isset($args[1])) {
            $data = $args[1];
        }

        $this->load->model('extension/ps_product_category_filter/module/ps_product_category_filter');

        $this->model_extension_ps_product_category_filter_module_ps_product_category_filter->addFilters($data);
    }

    public function eventAdminViewCatalogCategoryFormBefore(string &$route, array &$args, string &$template): void
    {
        if (!$this->config->get('module_ps_product_category_filter_status')) {
            return;
        }

        $separator = version_compare(VERSION, '4.0.2.0', '>=') ? '.' : '|';

        $this->load->model('extension/ps_product_category_filter/module/ps_product_category_filter');

        if ($this->config->get('module_ps_product_category_filter_status')) {
            $this->load->language('extension/ps_product_category_filter/module/ps_product_category_filter', 'ps');

            $args['button_remove_filter'] = $this->language->get('ps_button_remove_filter');


            $url = '';

            if (isset($this->request->get['sort'])) {
                $url .= '&sort=' . $this->request->get['sort'];
            }

            if (isset($this->request->get['order'])) {
                $url .= '&order=' . $this->request->get['order'];
            }

            if (isset($this->request->get['page'])) {
                $url .= '&page=' . $this->request->get['page'];
            }

            if (isset($this->request->get['category_id'])) {
                $url .= '&category_id=' . $this->request->get['category_id'];
            }

            $args['remove_filter'] = $this->url->link('extension/ps_product_category_filter/module/ps_product_category_filter' . $separator . 'removeunused', 'user_token=' . $this->session->data['user_token'] . $url, true);
        } else {
            $args['remove_filter'] = '';
        }

        $headerViews = $this->model_extension_ps_product_category_filter_module_ps_product_category_filter->replaceHeaderViews($args);

        $template = $this->replaceViews($route, $template, $headerViews);
    }

    public function removeunused()
    {
        $this->load->language('extension/ps_product_category_filter/module/ps_product_category_filter');

        $this->load->model('extension/ps_product_category_filter/module/ps_product_category_filter');

        if (isset($this->request->get['category_id'])) {
            $category_id = (int) $this->request->get['category_id'];
        } else {
            $category_id = 0;
        }

        $this->model_extension_ps_product_category_filter_module_ps_product_category_filter->removeUnusedFilters($category_id);

        $this->session->data['success'] = $this->language->get('text_filter_success');

        $url = '';

        if (isset($this->request->get['sort'])) {
            $url .= '&sort=' . $this->request->get['sort'];
        }

        if (isset($this->request->get['order'])) {
            $url .= '&order=' . $this->request->get['order'];
        }

        if (isset($this->request->get['page'])) {
            $url .= '&page=' . $this->request->get['page'];
        }

        $this->response->redirect($this->url->link('catalog/category', 'user_token=' . $this->session->data['user_token'] . $url));
    }

    /**
     * Retrieves the contents of a template file based on the provided route.
     *
     * This method checks if an event template buffer is provided. If so, it returns that buffer.
     * If not, it constructs the template file path based on the current theme settings and checks
     * for the existence of the template file. If the file exists, it reads and returns its contents.
     * It supports loading templates from both the specified theme directory and the default template directory.
     *
     * @param string $route The route for which the template is being retrieved.
     *                      This should match the naming convention for the template files.
     * @param string $event_template_buffer The template buffer that may be passed from an event.
     *                                       If provided, this buffer will be returned directly,
     *                                       bypassing file retrieval.
     *
     * @return mixed Returns the contents of the template file as a string if it exists,
     *               or false if the template file cannot be found or read.
     */
    protected function getTemplateBuffer(string $route, string $event_template_buffer): mixed
    {
        if ($event_template_buffer) {
            return $event_template_buffer;
        }

        if (defined('DIR_CATALOG')) {
            $dir_template = DIR_TEMPLATE;
        } else {
            if ($this->config->get('config_theme') == 'default') {
                $theme = $this->config->get('theme_default_directory');
            } else {
                $theme = $this->config->get('config_theme');
            }

            $dir_template = DIR_TEMPLATE . $theme . '/template/';
        }

        $template_file = $dir_template . $route . '.twig';

        if (file_exists($template_file) && is_file($template_file)) {
            $template_file = $this->modCheck($template_file);

            return file_get_contents($template_file);
        }

        if (defined('DIR_CATALOG')) {
            return false;
        }

        $dir_template = DIR_TEMPLATE . 'default/template/';
        $template_file = $dir_template . $route . '.twig';

        if (file_exists($template_file) && is_file($template_file)) {
            $template_file = $this->modCheck($template_file);

            return file_get_contents($template_file);
        }

        // Support for OC4 catalog
        $dir_template = DIR_TEMPLATE;
        $template_file = $dir_template . $route . '.twig';

        if (file_exists($template_file) && is_file($template_file)) {
            $template_file = $this->modCheck($template_file);

            return file_get_contents($template_file);
        }

        return false;
    }

    /**
     * Checks and modifies the provided file path based on predefined directory constants.
     *
     * This method checks if the file path starts with specific directory constants (`DIR_MODIFICATION`,
     * `DIR_APPLICATION`, and `DIR_SYSTEM`). Depending on these conditions, it modifies the file path to
     * point to the appropriate directory under `DIR_MODIFICATION`.
     *
     * - If the file path starts with `DIR_MODIFICATION`, it checks if it should point to either the
     *   `admin` or `catalog` directory based on the definition of `DIR_CATALOG`.
     * - If `DIR_CATALOG` is defined, the method checks for the file in the `admin` directory.
     *   Otherwise, it checks in the `catalog` directory.
     * - If the file path starts with `DIR_SYSTEM`, it checks for the file in the `system` directory
     *   within `DIR_MODIFICATION`.
     *
     * The method ensures that the returned file path exists before modifying it.
     *
     * @param string $file The original file path to check and modify.
     * @return string|null The modified file path if found, or null if it does not exist.
     */
    protected function modCheck(string $file): mixed
    {
        if (defined('DIR_MODIFICATION')) {
            if ($this->startsWith($file, DIR_MODIFICATION)) {
                if (defined('DIR_CATALOG')) {
                    if (file_exists(DIR_MODIFICATION . 'admin/' . substr($file, strlen(DIR_APPLICATION)))) {
                        $file = DIR_MODIFICATION . 'admin/' . substr($file, strlen(DIR_APPLICATION));
                    }
                } else {
                    if (file_exists(DIR_MODIFICATION . 'catalog/' . substr($file, strlen(DIR_APPLICATION)))) {
                        $file = DIR_MODIFICATION . 'catalog/' . substr($file, strlen(DIR_APPLICATION));
                    }
                }
            } elseif ($this->startsWith($file, DIR_SYSTEM)) {
                if (file_exists(DIR_MODIFICATION . 'system/' . substr($file, strlen(DIR_SYSTEM)))) {
                    $file = DIR_MODIFICATION . 'system/' . substr($file, strlen(DIR_SYSTEM));
                }
            }
        }

        return $file;
    }

    /**
     * Checks if a given string starts with a specified substring.
     *
     * This method determines if the string $haystack begins with the substring $needle.
     *
     * @param string $haystack The string to be checked.
     * @param string $needle The substring to search for at the beginning of $haystack.
     *
     * @return bool Returns true if $haystack starts with $needle; otherwise, false.
     */
    protected function startsWith(string $haystack, string $needle): bool
    {
        if (strlen($haystack) < strlen($needle)) {
            return false;
        }

        return (substr($haystack, 0, strlen($needle)) == $needle);
    }

    /**
     * Replaces specific occurrences of a substring in a string with a new substring.
     *
     * This method searches for all occurrences of a specified substring ($search) in a given string ($string)
     * and replaces the occurrences at the positions specified in the $nthPositions array with a new substring ($replace).
     *
     * @param string $search The substring to search for in the string.
     * @param string $replace The substring to replace the found occurrences with.
     * @param string $string The input string in which replacements will be made.
     * @param array $nthPositions An array of positions (1-based index) indicating which occurrences
     *                            of the search substring to replace.
     *
     * @return mixed The modified string with the specified occurrences replaced, or the original string if no matches are found.
     */
    protected function replaceNth(string $search, string $replace, string $string, array $nthPositions): mixed
    {
        $pattern = '/' . preg_quote($search, '/') . '/';
        $matches = [];
        $count = preg_match_all($pattern, $string, $matches, PREG_OFFSET_CAPTURE);

        if ($count > 0) {
            foreach ($nthPositions as $nth) {
                if ($nth > 0 && $nth <= $count) {
                    $offset = $matches[0][$nth - 1][1];
                    $string = substr_replace($string, $replace, $offset, strlen($search));
                }
            }
        }

        return $string;
    }

    /**
     * Replaces placeholders in a template with corresponding values from the views array.
     *
     * This method retrieves the template content based on the given route and template name,
     * then replaces specified search strings with their corresponding replace strings.
     * If positions are specified, the method performs replacements only at those positions.
     *
     * @param string $route The route associated with the template.
     * @param string $template The name of the template to be processed.
     * @param array $views An array of associative arrays where each associative array contains:
     *                     - string 'search': The string to search for in the template.
     *                     - string 'replace': The string to replace the 'search' string with.
     *                     - array|null 'positions': (Optional) An array of positions
     *                     where replacements should occur. If not provided,
     *                     all occurrences will be replaced.
     *
     * @return mixed The modified template content after performing the replacements.
     */
    protected function replaceViews(string $route, string $template, array $views): mixed
    {
        $output = $this->getTemplateBuffer($route, $template);

        foreach ($views as $view) {
            if (isset($view['positions']) && $view['positions']) {
                $output = $this->replaceNth($view['search'], $view['replace'], $output, $view['positions']);
            } else {
                $output = str_replace($view['search'], $view['replace'], $output);
            }
        }

        return $output;
    }
}
