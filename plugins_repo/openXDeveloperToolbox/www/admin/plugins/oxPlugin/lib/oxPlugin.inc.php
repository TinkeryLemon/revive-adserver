<?php

/*
+---------------------------------------------------------------------------+
| Revive Adserver                                                           |
| http://www.revive-adserver.com                                            |
|                                                                           |
| Copyright: See the COPYRIGHT.txt file.                                    |
| License: GPLv2 or later, see the LICENSE.txt file.                        |
+---------------------------------------------------------------------------+
*/

/**
 * OpenX Developer Toolbox
 */

function getExtensionList()
{
    require_once(LIB_PATH . '/Extension.php');
    return OX_Extension::getAllExtensionsArray();
}

class OX_PluginBuilder_Common
{
    public $aTemplates;
    public $aValues;

    public $aRegPattern;
    public $aRegReplace;

    public $pathPlugin;

    public function init($aVals)
    {
        global $pathPlugin;

        $this->pathPlugin = $pathPlugin;

        $this->aValues = $aVals;

        $this->aTemplates['HEADER'] = 'header.xml.tpl';
        $this->aTemplates['FILES'] = 'files.xml.tpl';

        foreach ($aVals as $k => $v) {
            $this->aRegPattern[] = '/\{' . strtoupper($k) . '\}/';
            $this->aRegReplace[] = $v;
        }
    }

    public function _compileTemplate($tag, &$data)
    {
        if (!array_key_exists($tag, $this->aTemplates)) {
            $data = str_replace('{' . strtoupper($tag) . '}', '', $data);
            return;
        }
        if (!file_exists('etc/elements/' . $this->aTemplates[$tag])) {
            return;
        }
        $dataSource = file_get_contents('etc/elements/' . $this->aTemplates[$tag]);
        if (!$dataSource) {
            return;
        }
        $this->_replaceTags($dataSource);
        $data = str_replace('{' . strtoupper($tag) . '}', $dataSource, $data);
    }

    public function _replaceTags(&$subject)
    {
        $result = preg_replace(array_values($this->aRegPattern), array_values($this->aRegReplace), $subject);
        $subject = ($result ? $result : $subject);
        return;
    }

    public function _renameFile($needle, $file)
    {
        if (substr_count($file, $needle)) {
            copy($file, str_replace($needle, $this->aValues[$needle], $file));
            unlink($file);
        }
    }

    public function _compileFiles($dir, $replace = '')
    {
        $dh = opendir($dir);
        if ($dh) {
            while (false !== ($file = readdir($dh))) {
                if (substr($file, 0, 1) != '.') {
                    if (is_dir($dir . '/' . $file)) {
                        $this->_compileFiles($dir . '/' . $file, $replace);
                    } else {
                        $contents = file_get_contents($dir . '/' . $file);
                        $this->_replaceTags($contents);
                        $i = file_put_contents($dir . '/' . $file, $contents);
                        if ($replace) {
                            $this->_renameFile($replace, $dir . '/' . $file);
                        }
                    }
                }
            }
            closedir($dh);
        }
    }
}

class OX_PluginBuilder_Group extends OX_PluginBuilder_Common
{
    public $pathGroup;
    public $pathComponents;
    public $schema = false;

    public function init($aVals)
    {
        parent::init($aVals);

        $xmlTemplate = 'files-' . $this->aValues['extension'] . '.xml.tpl';
        if (file_exists('etc/elements/' . $xmlTemplate)) {
            $this->aTemplates['FILES'] = $xmlTemplate;
        }
        if ($aVals['extension'] == 'admin') {
            $this->aTemplates['NAVIGATION'] = 'navigation.xml.tpl';
        }
        if ($this->schema) {
            $this->aTemplates['SCHEMA'] = 'schema.xml.tpl';
        }

        // Component class file
        $classFile = $this->aValues['extension'] . 'Component.class.php';
        if (file_exists('etc/components/' . $classFile)) {
            $this->aTemplates['COMPONENTS'][$this->aValues['extension']][] = $classFile;
        }

        // Component delivery file
        $deliveryFile = $this->aValues['extension'] . 'Component.delivery.php';
        if (file_exists('etc/components/' . $deliveryFile)) {
            $this->aTemplates['COMPONENTS'][$this->aValues['extension']][] = $deliveryFile;
        }

        global $pathPackages;
        $this->pathGroup = $pathPackages . $aVals['group'] . '/';

        // Create the components dir (is there a better place to do this?)
        $this->pathComponents = $pathPackages . "../{$this->aValues['extension']}/{$aVals['name']}/";
        mkdir($this->pathComponents, 0777, true);
    }

    public function putGroup()
    {
        $groupDefinitionFile = $this->pathGroup . 'group.xml';

        $dataTarget = file_get_contents($groupDefinitionFile);

        $this->_compileTemplate('HEADER', $dataTarget);
        $this->_compileTemplate('FILES', $dataTarget);
        $this->_compileTemplate('NAVIGATION', $dataTarget);
        $this->_compileTemplate('SCHEMA', $dataTarget);
        $i = file_put_contents($groupDefinitionFile, $dataTarget);

        copy($groupDefinitionFile, str_replace('group', $this->aValues['name'], $groupDefinitionFile));
        unlink($groupDefinitionFile);
        $this->_compileFiles($this->pathPlugin, 'group');

        // Copy the component files.
        foreach ($this->aTemplates['COMPONENTS'][$this->aValues['extension']] as $file) {
            $dest = str_replace($this->aValues['extension'], $this->aValues['name'], $file);
            copy('etc/components/' . $file, $this->pathComponents . $dest);
        }
        $this->_compileFiles($this->pathComponents);
    }
}

class OX_PluginBuilder_Package extends OX_PluginBuilder_Common
{
    public $pathPackages;

    public function init($aVals)
    {
        parent::init($aVals);

        global $pathPackages;
        $this->pathPackages = $pathPackages;
    }

    public function putPlugin()
    {
        $pluginDefinitionFile = $this->pathPackages . 'plugin.xml';
        $data = file_get_contents($pluginDefinitionFile);
        $this->_compileTemplate('HEADER', $data);

        $groups = '';
        foreach ($this->aValues['grouporder'] as $i => $group) {
            $groups .= "            <group name=\"{$group}\">{$i}</group>\n";
        }
        $data = str_replace('{GROUPS}', $groups, $data);
        $data = str_replace('{NAME}', $this->aValues['name'], $data);
        $i = file_put_contents($pluginDefinitionFile, $data);

        copy($pluginDefinitionFile, str_replace('plugin.xml', $this->aValues['name'] . '.xml', $pluginDefinitionFile));
        unlink($pluginDefinitionFile);

        $pluginReadmeFile = $this->pathPackages . 'plugin.readme.txt';
        copy($pluginReadmeFile, str_replace('plugin.readme.txt', $this->aValues['name'] . '.readme.txt', $pluginReadmeFile));
        unlink($pluginReadmeFile);
    }
}
