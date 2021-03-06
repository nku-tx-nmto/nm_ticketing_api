<?php
/**
 * Created by PhpStorm.
 * User: 31832
 * Date: 2019/3/16
 * Time: 20:19
 */
namespace common\models;

use common\exceptions\ValidateException;
use Yii;

/*
 * 组织者表单
 * */
class OrganizerForm extends BaseForm
{
    public $category;
    public $credential;
    public $org_name;
    public $org_id;//用于给validatePassword方法传递模型实例
    public $status;

    //修改密码所用到的
    public $password;
    public $rePassword;
    public $oldPassword;


    public function rules()
    {
        return
            [
                [//create场景用到的必须字段
                    [
                        'org_name',
                        'credential',
                        'password',
                        'rePassword',
                        'category',
                        'status',
                    ],
                    'required',
                    'on'=>['Create','default',],
                ],
                [//update场景用到的必须字段
                    [
                        'org_name',
                        'category',
                        'status',
                    ],
                    'required',
                    'on'=>['Update','default',],
                ],
                [
                    [
                        'category',
                        'status',
                    ],
                    'integer',
                    'on'=>['Update','Create','default',],
                ],

                [['credential',], 'unique','on'=>['Create',]],

                ['status', 'in', 'range' => [Organizer::STATUS_ACTIVE, Organizer::STATUS_DELETED],'on'=>['Create','Update','default',]],
                [
                    'category', 'compare',
                    'compareValue'=>0,
                    'operator' => '>=','message'=>'分类无效',
                    'on'=>['Create','Update','default',],
                ],
                [
                    'category', 'compare',
                    'compareValue'=>count(ORG_CATEGORY),
                    'operator' => '<','message'=>'分类无效',
                    'on'=>['Create','Update','default',],
                ],

                [['org_name'], 'string', 'max' => 32,'on'=>['Create','Update','default',]],

                [['credential',], 'string', 'max' => 255,'on'=>['Create','default',]],
                [
                    ['credential'],
                    'unique', 'skipOnError' => true,
                    'targetClass' => Organizer::className(),
                    'targetAttribute' => ['credential' => 'credential'],
                    'message' => '这个账号已经被注册',
                    'on'=>['Create','default',]
                ],
                [['password'], 'string', 'max' => 255,'on'=>['Create','RePassword','default',]],

                [['password','rePassword'], 'string', 'min' => 6,'on'=>['RePassword','RePasswordByAdmin','Create','default',]],
                [['password','rePassword',], 'required','on'=>['RePassword','RePasswordByAdmin','Create','default',]],
                [['oldPassword',], 'required','on'=>['RePassword','default',]],
                //重复密码必须与密码相等
                ['rePassword','compare','compareAttribute'=>'password','message'=>'密码和重复密码不相同','on'=>['RePassword','RePasswordByAdmin','Create','default',]],
                ['oldPassword', 'validatePassword','on'=>['RePassword','default',]],
            ];
    }

    public static function tableName()
    {
        return 'tk_organizer';
    }

    //设置场景值
    public function scenarios()
    {
        return
            [
            'Create' =>//表示某个场景所用到的信息,没标记出来的不会有影响
                [
                    'auth_key',
                    'org_name',
                    'category',
                    'credential',
                    'password',
                    'rePassword',
                    'created_at',
                    'status',
                    'updated_at',
                ],
            'Update'=>
                [
                    'org_name',
                    'category',
                    'status',
                    'updated_at',
                ],
            'RePassword' =>
                [
                    'password',
                    'oldPassword',
                    'rePassword',
                    'updated_at',
                ],
            'RePasswordByAdmin' =>
                [
                    'password',
                    'rePassword',
                    'updated_at',
                ],
             'default'=>
                 [
                     'auth_key',
                     'org_name',
                     'category',
                     'credential',
                     'password',
                     'rePassword',
                     'oldPassword',
                     'created_at',
                     'status',
                     'updated_at',
                 ],
        ];
    }

    //rules中调用的验证旧密码的函数
    public function validatePassword($attribute)
    {
        if (!$this->hasErrors())
        {
            $org=Organizer::findIdentity($this->org_id);
            if (!$org || !$org->validatePassword($this->oldPassword)) {
                $this->addError($attribute, '旧密码不正确');
            }
        }
    }

    public function attributeLabels()
    {
        return
            [
                'org_name'=>'组织者名称',
                'credential'=>'账号',
                'category'=>'组织者分类',
                'password'=>'密码',
                'rePassword'=>'重复密码',
                'oldPassword'=>'旧密码',
                'status'=>'状态',
            ];
    }


    /**
     * 根据这个表单的信息创建一个账号,返回新创建的模型
     * 必须的字段为:
     * org_name,category,credential,password,rePassword,
     * status
     * @return Organizer
     * @throws ValidateException
     * @throws \Exception
     */
    public function create()
    {
        $this->scenario='Create';
        try
        {
            $model=$this->createAction_FillModel();
        }
        catch (\Exception $exception)
        {
            throw new \Exception(sprintf('OrganizerForm::create:获取auth_key失败:%s',$exception->getMessage()));
        }

        $transaction=Yii::$app->db->beginTransaction();
        try
        {
            if(!$this->validate())
                $this->throwValidateException('OrganizerForm::create:创建信息需要修改');

            //设置密码函数会抛出一个\Exception的异常,所以放在这
            $model->setPassword($this->password);

            if(!$model->save())
                $this->throwValidateException('OrganizerForm::create:创建模型失败');

            //此处可以写一个afterCreate方法来处理创建后事务

            $transaction->commit();
            return $model;
        }
        catch(ValidateException $exception)
        {
            $transaction->rollBack();
            throw $exception;
        }
        catch(\Exception $exception)
        {
            $transaction->rollBack();
            throw new \Exception(sprintf('OrganizerForm::create:设置密码发生异常:%s',$exception->getMessage()));
        }
    }

    /**
     * 根据表单的信息更新$model的信息,返回是否修改成功
     * 必须的字段为:
     * status,category,org_name
     * @param $model Organizer
     * @return bool
     * @throws ValidateException
     * @throws \Exception
     */
    public function infoUpdate($model)
    {
        $this->scenario='Update';
        $transaction=Yii::$app->db->beginTransaction();
        try
        {
            if(!$this->validate())
                $this->throwValidateException('OrganizerForm::infoUpdate:修改信息需要调整');

            $model->status=$this->status;
            $model->category=$this->category;
            $model->org_name=$this->org_name;

            if(!$model->save())
                $this->throwValidateException('OrganizerForm::infoUpdate:资料修改失败!');
            $transaction->commit();
            return true;
        }
        catch (ValidateException $exception)
        {
            $transaction->rollBack();
            throw $exception;
        }
        catch(\Exception $exception)
        {
            $transaction->rollBack();
            throw $exception;
        }
    }

    //向数据库更新该模型对应的修改的密码,返回是否修改成功
    /*
     * 必须的字段:password,rePassword,
     * 第二个参数为true时oldPassword也是必须的
     * */
    /**
     * @param Organizer $model
     * @param bool $validateOldPassword
     * @return bool
     * @throws ValidateException
     * @throws \Exception
     */
    public function RePassword($model,$validateOldPassword=true)
    {
        $this->scenario=($validateOldPassword)?'RePassword':'RePasswordByAdmin';
        $transaction=Yii::$app->db->beginTransaction();
        try
        {
            if(!$this->validate())
                $this->throwValidateException('OrganizerForm::RePassword:修改信息需要调整');

            $model->setPassword($this->password);

            if(!$model->save())
                $this->throwValidateException('OrganizerForm::RePassword:密码修改失败');

            $transaction->commit();
            return true;
        }
        catch(ValidateException $exception)
        {
            $transaction->rollBack();
            throw $exception;
        }
        catch(\Exception $exception)
        {
            $transaction->rollBack();
            throw $exception;
        }
    }

    /**
     * 将表单的信息生成一个组织者模型
     * @return Organizer
     * @throws \Exception
     */
    private function createAction_FillModel()
    {
        $model = new Organizer();
        $model->org_name = $this->org_name;
        $model->category=$this->category;
        $model->credential = $this->credential;
        $model->status=$this->status;
        $model->generateAuthKey();//原理不明，保留就对了，据说是用于自动登录的
        $model->access_token=' ';
        $model->wechat_id=' ';
        $model->expire_at = 0;
        $model->allowance = 2;
        $model->allowance_updated_at = 0;
        return $model;
    }
}
