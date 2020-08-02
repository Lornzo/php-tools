<?php
if(!class_exists("cp_form_components")){require(__DIR__."/cp_form_components.class.php");}
class cp_form_thing extends cp_form_components{
    
    
    
    public function getComponent() {
        $this->textBox("url", "url");
    }
}
?>