<?php
if(!class_exists("cp_form_thing")){require(__DIR__."/cp_form_thing.class.php");}
class cp_form_organization extends cp_form_thing{
    protected function _getComponents() {
        parent::_getComponents();
        $this->getTextBox("cc", "bb");
    }
}
?>