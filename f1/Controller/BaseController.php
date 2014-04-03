<?php
/**
 * @author   Jokūbas Ramanauskas
 * @since    3/30/14
 */

namespace Controller;


abstract class BaseController
{
    private $layout = 'layout.phtml';

    /**
     * Renders given template
     *
     * @param       $template
     * @param array $variables
     *
     * @return bool
     * @throws \Exception
     */
    protected function render($template, $variables = array())
    {
        if (!preg_match("/\.(?i)(htm?l|phtml)$/", $template)) {
            $template .= '.phtml';
        }

        if (!file_exists(VIEW_DIR . $template)) {
            throw new \Exception(printf('File %s not found in path %s.', VIEW_DIR, VIEW_DIR . $template));
        }

        if (!empty($variables)) {
            foreach ($variables as $name => $value) {
                $$name = $value;
            }
        }

        ob_start();
        include VIEW_DIR . $template;
        $content = ob_get_contents();
        ob_end_clean();

        include_once($this->getLayout());

        return true;
    }

    protected function renderAjax($template, $variables = array())
    {
        /**@todo refactor this */

        if (!preg_match("/\.(?i)(htm?l|phtml)$/", $template)) {
            $template .= '.phtml';
        }

        if (!file_exists(VIEW_DIR . $template)) {
            throw new \Exception(printf('File %s not found in path %s.', VIEW_DIR, VIEW_DIR . $template));
        }

        if (!empty($variables)) {
            foreach ($variables as $name => $value) {
                $$name = $value;
            }
        }

        ob_start();
        include VIEW_DIR . $template;

        $incredibleVariableName = hash('md5', microtime());
        $incredibleVariableName = ob_get_contents();
        ob_end_clean();

        return $incredibleVariableName;
    }

    /**
     * @return mixed
     */
    public function getLayout()
    {
        return VIEW_DIR . $this->layout;
    }
} 