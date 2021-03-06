<?php

/* @var $this yii\web\View */
use yii\helpers\Html;
use yii\widgets\ActiveForm;
$this->title = '修改密码';
$this->params['breadcrumbs'][] = ['label' => '我的资料', 'url' => ['view']];
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="col-lg-8">
<div class="admin-form">
    <h1>修改密码:</h1>
    <?php $form = ActiveForm::begin(); ?>

    <?= $form->field($model, 'oldPassword')->passwordInput()?>

    <?= $form->field($model, 'password')->passwordInput()?>

    <?= $form->field($model, 'rePassword')->passwordInput()?>

    <div class="form-group">
        <?= Html::submitButton('确认修改', ['class' => 'btn btn-success']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
</div>
