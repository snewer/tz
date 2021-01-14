<?php

namespace app\models;

use yii\behaviors\TimestampBehavior;
use yii\db\Expression;
use yii\data\ActiveDataProvider;

/**
 * @property int $id
 * @property string $created_at
 * @property string $updated_at
 * @property string $email
 * @property string $phone
 * @property string|null $text
 * @property int|null $manager_id
 *
 * @property Manager|null $manager
 */
class Request extends \yii\db\ActiveRecord
{
    public static function tableName()
    {
        return 'requests';
    }

    public function behaviors()
    {
        return [
            [
                'class' => TimestampBehavior::class,
                'value' => new Expression('NOW()'),
            ]
        ];
    }

    public function rules()
    {
        return [
            [['email', 'phone'], 'required'],
            ['email', 'email'],
            ['manager_id', 'integer'],
            ['manager_id', 'exist', 'targetClass' => Manager::class, 'targetAttribute' => 'id'],
            [['email', 'phone'], 'string', 'max' => 255],
            ['text', 'safe'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'created_at' => 'Добавлен',
            'updated_at' => 'Изменен',
            'email' => 'Email',
            'phone' => 'Номер телефона',
            'manager_id' => 'Ответственный менеджер',
            'text' => 'Текст заявки',
        ];
    }

    public function getManager()
    {
        return $this->hasOne(Manager::class, ['id' => 'manager_id']);
    }

    public static function findLastWithoutDuplicates()
    {
        $query = Request::find()->alias('req1');
        $query->with(['manager']);
        
        /*
        Select last created 'duplicate' rows according to task
            SELECT *
            FROM requests AS req1
            WHERE id = (
                SELECT max(id)
                FROM requests AS req2
                WHERE DATEDIFF(NOW(), req2.created_at) < 30
                AND (req1.email = req2.email OR req1.phone = req2.phone)
                ORDER BY id DESC
            )
        */
        $subQuery = Request::find()
            ->alias('req2')
            ->select(['max(req2.id)'])
            ->where(['<', 'DATEDIFF(NOW(), req2.created_at)', 30])
            ->andWhere(['OR', 'req1.email = req2.email', 'req1.phone = req2.phone']);

        $query->andWhere(['=', 'req1.id', $subQuery]);

        return $query;

    }

    public static function findPreviousOne(Request $req)
    {
       /*
        SELECT * 
        FROM requests req1 
        WHERE id = ( 
            SELECT id 
            FROM requests req2 
            WHERE DATEDIFF(NOW(), req2.created_at) < 30 
                AND (req1.email = req2.email OR req1.phone = req2.phone) 
                AND (req1.email = 'test@test.com' OR req1.phone = '1234') 
            ORDER BY ID DESC 
            LIMIT 1,1 
        );
       */
       $query = Request::find()->alias('req1');
         $query->with(['manager']);
        
         $subQuery = Request::find()
             ->alias('req2')
             ->select(['id'])
             ->where(['<', 'DATEDIFF(req1.created_at, req2.created_at)', 30])
             ->andWhere(['OR', 'req1.email = :email', 'req1.phone = :phone'], ['email' => $req->email, 'phone' => $req->phone])
             ->andWhere(['OR', 'req1.email = req2.email', 'req1.phone = req2.phone'])
             ->orderBy(['id' => SORT_DESC])
             ->limit(1)
             ->offset(1);

         $query->andWhere(['=', 'req1.id', $subQuery]);

         return $query->one();
    }
}
