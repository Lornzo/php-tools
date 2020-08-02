<?php 
class cp_form_components{
    
    /**
     * @var array 有預設資料的時候放這裡面
     */
    protected $_data;
    
    /**
     * 建立一個bootstrap form text box
     * @param string $name 提示輸入的名稱
     * @param string $col_name 欄位名稱
     * @param string $value 預設值
     * @param bool $required 是否開放空值
     * @param string $place_holder 是示輸入
     */
    public function textBox(string $name,string $col_name,string $value="",bool $required=false,string $place_holder=""){
        ?>
        <div class="form-group row">
            <label class="col-sm-2 col-form-label"><?php echo $name;?></label>
            <div class="col-sm-10">
                <input type="text" name="<?php echo $col_name;?>" value="<?php echo $value;?>" class="form-control"<?php if($required){?> required<?php }?><?php if(!empty($place_holder)){?> placeholder="<?php echo $place_holder;?>"<?php }?>>
            </div>
        </div>
        <?php
    }
    
    /**
     * 設定初始資料
     * @param array $data
     * @return $this
     */
    public function setData($data){$this->_data = $data;return $this;}
    
    /**
     * 取得模板
     */
    public function getComponent(){}
}

?>