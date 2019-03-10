<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "{{%tk_activity}}".
 *
 * @property int $id
 * @property string $activity_name
 * @property int $release_by organizer/-id
 * @property int $category 标记用户类别0-学生1-教职员工2-其他
 * @property int $status 
 * @property string $location
 * @property string $release_at
 * @property int $start_at
 * @property string $end_at
 * @property int $updated_at
 * @property string $introduction 介绍
 * @property int $current_people
 * @property int $max_people
 * @property int $current_serial 用于产生票务的序列号
 * @property string $pic_url 暂不支持传入图片
 *
 * @property Organizer $releaseBy
 * @property ActivityEvent[] $tkActivityEvents
 * @property Ticket[] $tkTickets
 * @property TicketEvent[] $tkTicketEvents
 */
class Activity extends \yii\db\ActiveRecord
{
    const STATUS_UNAUDITED  = 0;//未审核状态
    const STATUS_APPROVED = 1;//已批准状态
    const STATUS_REJECTED= 2;//被驳回状态
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%tk_activity}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['release_by', 'category', 'status', 'start_at','end_at', 'updated_at', 'current_people', 'max_people', 'current_serial'], 'integer'],
            [['release_at'], 'safe'],
            [['activity_name'], 'string', 'max' => 32],
            [['location'], 'string', 'max' => 64],
            [[ 'introduction',], 'string', 'max' => 255],
            [['release_by'], 'exist', 'skipOnError' => true, 'targetClass' => Organizer::className(), 'targetAttribute' => ['release_by' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'activity_name' => '活动名称',
            'release_by' => '发布者ID',
            'category' => '活动类别',
            'status' => '证件号',
            'location' => '活动地点',
            'release_at' => '发布时间',
            'start_at' => '活动开始时间',
            'end_at' => '活动结束时间',
            'ticketing_start_at'=>'票务开始时间',
            'ticketing_end_at'=>'票务结束时间',
            'updated_at' => '字段更新时间',
            'created_at'=>'字段创建时间',
            'introduction' => '介绍',
            'current_people' => '当前人数',
            'max_people' => '最大人数',
            'current_serial' => '票务的序列号',
            'pic_url' => '图片',
        ];
    }

    //用于admin端查找活动的名称
    public static function findIdentity_admin($id)
    {
        return static::findOne(['id' => $id]);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getReleaseBy()
    {
        return $this->hasOne(Organizer::className(), ['id' => 'release_by']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getActivityEvents()
    {
        return $this->hasMany(ActivityEvent::className(), ['activity_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getTickets()
    {
        return $this->hasMany(Ticket::className(), ['activity_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getTicketEvents()
    {
        return $this->hasMany(TicketEvent::className(), ['activity_id' => 'id']);
    }

    public function fields()
    {
        return [
            "id",
            "activity_name",
            "organizer_id" => "release_by",
            "organizer_name" => function($model)
            {
                if($model->releaseBy == null)
                    return '无发布者';
                return $model->releaseBy->org_name;
            },
            "category" => function($model)
            {
                switch($model->category)
                {
                    case 0:
                        return '讲座';
                        break;
                    case 1:
                        return '文艺';
                        break;
                    case 2:
                        return '其他';
                        break;
                    default:
                        return '未知';
                        break;
                }
            },
            "status" => function($model)
            {
                switch ($model->status) 
                {
                    case 0:
                        return '正常';
                        break;
                    case 1:
                        return '取消';
                        break;
                    case 2:
                        return '结束';
                        break;
                    default:
                        return '未知';
                        break;
                }
            },
            "location",
            "release_at",
            "start_at",
            "end_at",
            "ticketing_start_at",
            "ticketing_end_at",
            "introduction",
            "current_people",
            "max_people",
        ];
    }

    public function generateAndWriteNewActivity($org_id,$activity_name,$category,$location,$ticketing_start_at,$ticketing_end_at,$start_at,$end_at,$max_people,$intro)
    {
        $activity = new Activity();
        //太可怕了
        $activity->release_by = $org_id;
        $activity->activity_name = $activity_name;
        $activity->category = $category;
        $activity->location = $location;
        $activity->ticketing_start_at = $ticketing_start_at;
        $activity->ticketing_end_at = $ticketing_end_at;
        $activity->start_at = $start_at;
        $activity->end_at = $end_at;
        $activity->release_at = time()+7*3600;
        $activity->max_people = $max_people;
        $activity->introduction = $intro;
        $activity->current_people = 0;
        $activity->current_serial = 1;
        $activity->save(false);

        return $activity;
    }

    public function editAndSaveActivity($activity,$activity_name,$category,$location,$ticketing_start_at,$ticketing_end_at,$start_at,$end_at,$max_people,$intro)
    {
        $activity->activity_name = $activity_name==null?$activity->activity_name:$activity_name;

        $activity->category = $category==null?$activity->category:$category;
        // $activity->name = $activity_name==null?$activity->name:$activity_name;
        $activity->location = $location==null?$activity->location:$location;
        $activity->ticketing_start_at = $ticketing_start_at==null?$activity->ticketing_start_at:$ticketing_start_at;
        $activity->ticketing_end_at = $ticketing_end_at==null?$activity->ticketing_end_at:$ticketing_end_at;
        $activity->start_at = $start_at==null?$activity->start_at:$start_at;
        $activity->end_at = $end_at==null?$activity->end_at:$end_at;
        $activity->max_people = $max_people==null?$activity->max_people:$max_people;
        $activity->introduction = $intro==null?$activity->introduction:$intro;
        $activity->save(false);
    }

    // public function extraFields()
    // {

    // }


}
