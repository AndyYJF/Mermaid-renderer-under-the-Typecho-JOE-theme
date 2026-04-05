<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;

/**
 * Mermaid 插件（JOE终极兼容版 / 前端解析）
 *
 * @package MermaidUltimate
 * @version 2.0.0
 */
class Mermaid_Plugin implements Typecho_Plugin_Interface
{
    public static function activate()
    {
        Typecho_Plugin::factory('Widget_Archive')->header = array('Mermaid_Plugin', 'header');
        Typecho_Plugin::factory('Widget_Archive')->footer = array('Mermaid_Plugin', 'footer');
    }

    public static function deactivate() {}

    public static function config(Typecho_Widget_Helper_Form $form)
    {
        $cdn = new Typecho_Widget_Helper_Form_Element_Text(
            'cdn',
            null,
            'https://cdn.jsdelivr.net/npm/mermaid@10/dist/mermaid.min.js',
            _t('Mermaid CDN'),
            _t('推荐 jsdelivr 或 npmmirror')
        );
        $form->addInput($cdn);

        $theme = new Typecho_Widget_Helper_Form_Element_Select(
            'theme',
            array(
                'default' => 'Default',
                'dark'    => 'Dark',
                'forest'  => 'Forest',
                'neutral' => 'Neutral',
            ),
            'default',
            _t('主题'),
            _t('Mermaid 渲染主题')
        );
        $form->addInput($theme);

        $autoDark = new Typecho_Widget_Helper_Form_Element_Radio(
            'autoDark',
            array(
                '1' => '开启',
                '0' => '关闭'
            ),
            '1',
            _t('自动暗黑模式'),
            _t('根据 JOE 主题自动切换')
        );
        $form->addInput($autoDark);
    }

    public static function personalConfig(Typecho_Widget_Helper_Form $form) {}

    public static function header()
    {
        echo '<style>
        .mermaid-container {
            text-align: center;
            margin: 1em 0;
        }
        </style>';
    }

    public static function footer()
    {
        $options  = Helper::options()->plugin('Mermaid');
        $cdn      = $options->cdn ?: 'https://cdn.jsdelivr.net/npm/mermaid@10/dist/mermaid.min.js';
        $theme    = $options->theme ?: 'default';
        $autoDark = $options->autoDark;

        echo <<<HTML
<script src="{$cdn}"></script>
<script>
(function () {

    function getTheme() {
        if ({$autoDark} == 1) {
            if (document.documentElement.classList.contains('dark') ||
                document.body.classList.contains('dark')) {
                return 'dark';
            }
        }
        return '{$theme}';
    }

    function convertMermaid() {
        // 找到所有 mermaid 代码块
        const blocks = document.querySelectorAll(
            'pre code.language-mermaid, pre.language-mermaid'
        );

        blocks.forEach(function(codeBlock) {

            // 防重复处理
            if (codeBlock.dataset.mermaidDone) return;
            codeBlock.dataset.mermaidDone = "1";

            let code = codeBlock.textContent;

            // 创建容器
            const container = document.createElement('div');
            container.className = 'mermaid-container';

            const mermaidDiv = document.createElement('div');
            mermaidDiv.className = 'mermaid';
            mermaidDiv.textContent = code;

            container.appendChild(mermaidDiv);

            // 替换整个 pre
            let pre = codeBlock.closest('pre');
            if (pre) {
                pre.replaceWith(container);
            } else {
                codeBlock.replaceWith(container);
            }
        });
    }

    function renderMermaid() {
        if (typeof mermaid === 'undefined') {
            console.warn('Mermaid not loaded');
            return;
        }

        try {
            mermaid.initialize({
                startOnLoad: false,
                theme: getTheme()
            });

            mermaid.init(undefined, document.querySelectorAll('.mermaid'));

        } catch (e) {
            console.error('Mermaid error:', e);
        }
    }

    function run() {
        convertMermaid();
        renderMermaid();
    }

    // 首次加载
    document.addEventListener('DOMContentLoaded', run);

    // JOE PJAX
    document.addEventListener('pjax:complete', function () {
        run();
    });

})();
</script>
HTML;
    }
}