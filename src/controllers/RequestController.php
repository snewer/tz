<?php

namespace app\controllers;

use Yii;
use app\models\Request;
use app\models\RequestSearch;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use app\models\Manager;

class RequestController extends Controller
{
    public function actionIndex()
    {
        $searchModel = new RequestSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    public function actionView($id)
    {
        return $this->render('view', [
            'model' => $this->findModel($id),
        ]);
    }

    public function actionCreate()
    {
        $model = new Request();

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            $prevReq = Request::findPreviousOne($model);
            $manager_id = null;
            // If model has previous record
            if ($prevReq)
            {
                // Trying to get manager from previous record
                $prevManager = Manager::findOne($prevReq->manager_id);
                if ($prevManager->is_works)
                    $manager_id = $prevManager->id;
            }
            // Getting manager with minimal request count
            // One manager is always working, so we don't process null case
            $manager_id = $manager_id ? $manager_id : Manager::getMinimalRequestCountOne()->id;
            // Set manager id
            $model->manager_id = $manager_id;
            $model->save();
            return $this->redirect(['view', 'id' => $model->id]);
        }

        return $this->render('create', [
            'model' => $model,
        ]);
    }

    public function actionUpdate($id)
    {
        $model = $this->findModel($id);

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['view', 'id' => $model->id]);
        }

        return $this->render('update', [
            'model' => $model,
        ]);
    }

    protected function findModel($id)
    {
        if (($model = Request::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }
}
