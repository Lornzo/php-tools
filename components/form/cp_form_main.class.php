<?php
class cp_form_main{

    protected $_data;

    public function setFormData($data){
        $this->_data = $data;return $this;
    }
    
    /**
     * 取得完整的表單
     * @param string $method
     * @param string $action
     */
    public function getForm(string $method,string $action=""){
        ?>
        <form method="<?php echo $method;?>" action="<?php echo $action;?>">
            <?php $this->_getComponents();?>
            <div class="form-group row"><div class="col-12"><button type="submit" class="btn btn-dark btn-block">Submit</button></div></div>
        </form>
        <?php
    }
    
    /**
     * 繼承的子類統一用這個function來為表單增加元素
     */
    protected function _getComponents(){}
}
?>