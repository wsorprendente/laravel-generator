<?php

namespace InfyOm\Generator\Generators\VueJs;

use InfyOm\Generator\Common\CommandData;
use InfyOm\Generator\Generators\BaseGenerator;
use InfyOm\Generator\Utils\FileUtil;
use InfyOm\Generator\Utils\TemplateUtil;

class ControllerGenerator extends BaseGenerator
{
    /** @var CommandData */
    private $commandData;

    /** @var string */
    private $path;

    /** @var string */
    private $fileName;

    public function __construct(CommandData $commandData)
    {
        $this->commandData = $commandData;
        $this->path = $commandData->config->pathApiController;
        $this->fileName = $this->commandData->modelName.'APIController.php';
    }

    public function generate()
    {
        $templateData = TemplateUtil::getTemplate('vuejs.controller.api_controller', 'laravel-generator');

        $templateData = TemplateUtil::fillTemplate($this->commandData->dynamicVars, $templateData);
        $templateData = $this->fillDocs($templateData);

        $fields = $this->commandData->inputFields;
        $i = 0;
        $fieldLenght = count($fields) ;
        $filter = '';  
        $searchableCount = 0;
        foreach ($fields as $field) {
            if ($field['searchable'])
                $searchableCount++;
        }      
        foreach ($fields as $field) {
            if ($field['searchable']) {
                if ($i == 0) {
                    $filter .= '$q->where("' . $field['fieldName'] . '", "like", $value)';
                    if ($searchableCount == 1) {
                        $filter .= ';';
                    } else {
                        $filter .= "\n";
                    }
                } else if ($i == $searchableCount - 1) {
                    $filter .= '                  ->orWhere("' . $field['fieldName'] . '", "like", $value);';
                } else {
                    $filter .= '                  ->orWhere("' . $field['fieldName'] . '", "like", $value)' . "\n";
                }
                $i++;
            }
        }
        $templateData = str_replace('$API_VUEJS_CONTROLLER_FILTER$', $filter, $templateData);
        FileUtil::createFile($this->path, $this->fileName, $templateData);

        $this->commandData->commandComment("\nAPI Controller created: ");
        $this->commandData->commandInfo($this->fileName);
    }

    private function fillDocs($templateData)
    {
        $methods = ['controller', 'index', 'store', 'show', 'update', 'destroy'];

        if ($this->commandData->getAddOn('swagger')) {
            $templatePrefix = 'controller';
            $templateType = 'swagger-generator';
        } else {
            $templatePrefix = 'api.docs.controller';
            $templateType = 'laravel-generator';
        }

        foreach ($methods as $method) {
            $key = '$DOC_'.strtoupper($method).'$';
            $docTemplate = TemplateUtil::getTemplate($templatePrefix.'.'.$method, $templateType);
            $docTemplate = TemplateUtil::fillTemplate($this->commandData->dynamicVars, $docTemplate);
            $templateData = str_replace($key, $docTemplate, $templateData);
        }

        return $templateData;
    }

    public function rollback()
    {
        if ($this->rollbackFile($this->path, $this->fileName)) {
            $this->commandData->commandComment('API Controller file deleted: '.$this->fileName);
        }
    }
}
