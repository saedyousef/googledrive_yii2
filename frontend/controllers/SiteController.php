<?php
namespace frontend\controllers;

use Yii;
use yii\base\InvalidParamException;
use yii\web\BadRequestHttpException;
use yii\web\Controller;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;
use common\models\LoginForm;
use frontend\models\PasswordResetRequestForm;
use frontend\models\ResetPasswordForm;
use frontend\models\SignupForm;
use frontend\models\ContactForm;

/**
 * Site controller
 */
class SiteController extends Controller
{
    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::className(),
                'only' => ['logout', 'signup', 'saed'],
                'rules' => [
                    [
                        'actions' => ['signup'],
                        'allow' => true,
                        'roles' => ['?'],
                    ],
                    [
                        'actions' => ['logout'],
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                    [
                        'actions' => ['saed'],
                        'allow' => true,
                        'roles' => ['?'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'logout' => ['post'],
                ],
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function actions()
    {
        return [
            'error' => [
                'class' => 'yii\web\ErrorAction',
            ],
            'captcha' => [
                'class' => 'yii\captcha\CaptchaAction',
                'fixedVerifyCode' => YII_ENV_TEST ? 'testme' : null,
            ],
        ];
    }

    /**
     * Displays homepage.
     *
     * @return mixed
     */
    public function actionIndex()
    {
        return $this->render('index');
    }

    /**
     * Logs in a user.
     *
     * @return mixed
     */
    public function actionLogin()
    {
        if (!Yii::$app->user->isGuest) {
            return $this->goHome();
        }

        $model = new LoginForm();
        if ($model->load(Yii::$app->request->post()) && $model->login()) {
            return $this->goBack();
        } else {
            $model->password = '';

            return $this->render('login', [
                'model' => $model,
            ]);
        }
    }

    /**
     * Logs out the current user.
     *
     * @return mixed
     */
    public function actionLogout()
    {
        Yii::$app->user->logout();

        return $this->goHome();
    }

    /**
     * Displays contact page.
     *
     * @return mixed
     */
    public function actionContact()
    {
        $model = new ContactForm();
        if ($model->load(Yii::$app->request->post()) && $model->validate()) {
            if ($model->sendEmail(Yii::$app->params['adminEmail'])) {
                Yii::$app->session->setFlash('success', 'Thank you for contacting us. We will respond to you as soon as possible.');
            } else {
                Yii::$app->session->setFlash('error', 'There was an error sending your message.');
            }

            return $this->refresh();
        } else {
            return $this->render('contact', [
                'model' => $model,
            ]);
        }
    }

    /**
     * Displays about page.
     *
     * @return mixed
     */
    public function actionAbout()
    {
        return $this->render('about');
    }

    /**
     * Signs user up.
     *
     * @return mixed
     */
    public function actionSignup()
    {
        $model = new SignupForm();
        if ($model->load(Yii::$app->request->post())) {
            if ($user = $model->signup()) {
                if (Yii::$app->getUser()->login($user)) {
                    return $this->goHome();
                }
            }
        }

        return $this->render('signup', [
            'model' => $model,
        ]);
    }

    /**
     * Requests password reset.
     *
     * @return mixed
     */
    public function actionRequestPasswordReset()
    {
        $model = new PasswordResetRequestForm();
        if ($model->load(Yii::$app->request->post()) && $model->validate()) {
            if ($model->sendEmail()) {
                Yii::$app->session->setFlash('success', 'Check your email for further instructions.');

                return $this->goHome();
            } else {
                Yii::$app->session->setFlash('error', 'Sorry, we are unable to reset password for the provided email address.');
            }
        }

        return $this->render('requestPasswordResetToken', [
            'model' => $model,
        ]);
    }

    /**
     * Resets password.
     *
     * @param string $token
     * @return mixed
     * @throws BadRequestHttpException
     */
    public function actionResetPassword($token)
    {
        try {
            $model = new ResetPasswordForm($token);
        } catch (InvalidParamException $e) {
            throw new BadRequestHttpException($e->getMessage());
        }

        if ($model->load(Yii::$app->request->post()) && $model->validate() && $model->resetPassword()) {
            Yii::$app->session->setFlash('success', 'New password saved.');

            return $this->goHome();
        }

        return $this->render('resetPassword', [
            'model' => $model,
        ]);
    }


    /**
    * This action will authenticate the user via OAuth Google Drive APIs
    *
    * @author Saed Yousef <saed.alzaben@gmail.com>
    *
    * @param code <Optional>
    * @param access_token <Optional>
    * @return string|mixed
    */
    public function actionDriveurl()
    {
        $response = Yii::$app->GoogleApiComponent->authenticateClient();
        if(!$response['authUrl'])
        {
            $data = $this->reformatResponse($response);

            return $this->render('files', [
                'files' => json_encode($data),
            ]);
        }

        return $this->redirect($response['authUrl']);
    }

    /**
    * This action is the callback for the Google Drive APIs to return the access_token/code
    *
    * @author Saed Yousef <saed.alzaben@gmail.com>
    *
    * @param code <Optional>
    * @param access_token <Optional>
    * @return string|mixed
    */
    public function actionCallback()
    {
        $request = Yii::$app->request;
        $code = $request->get('code');
        if(empty($code))
            $code = null;
        
        if(!empty($_SESSION['access_token']))
            $access_token = $_SESSION['access_token'];
        else
            $access_token = null;
        $response = Yii::$app->GoogleApiComponent->retrieveAllFiles($code, $access_token);
        if(!$response['authUrl'])
        {
            $data = $this->reformatResponse($response);

            return $this->render('files', [
                'files' => json_encode($data),
            ]);
        }
        return $this->redirect($response['authUrl']);
    }

    /**
    * This action will draw the json response return from Google Drive APIs
    *
    * @author Saed Yousef <saed.alzaben@gmail.com>
    * @param $files <JSON> 
    * @return view
    */
    public function actionFiles($files = null)
    {
        return $this->render('files', [
            'files' => $files,
        ]);
    }

    /**
    * This action will restructure the response from Google Drive APIs
    *
    * @author Saed Yousef <saed.alzaben@gmail.com>
    * @param $response <Array> object
    * @return array
    */
    protected function reformatResponse($response)
    {
        if(empty($response['files']))
            return null;

        $files = $response['files'];
        $filesResponse = [];
        foreach ($files->items as $key => $file) {
            $filesResponse[$key] = $file;
        }

        $data = [];
        
        foreach ($filesResponse as $index => $value) {
            if(isset($value['title']))
                $data[$index]['title'] = $value['title'];
            else
                $data[$index]['title'] = '-';
            
            if(isset($value['thumbnailLink']))
                $data[$index]['thumbnailLink'] = $value['thumbnailLink'];
            else
                $data[$index]['thumbnailLink'] = '#';
            
            if(isset($value['embedLink']))
                $data[$index]['embedLink'] = $value['embedLink'];
            else
                $data[$index]['embedLink'] = '#';

            if(isset($value['modifiedDate']))
                $data[$index]['modifiedDate'] = $value['modifiedDate'];
            else
                $data[$index]['modifiedDate'] = '-';

            if(isset($value['fileSize'])){
                if($value['fileSize'] > 0)
                    $data[$index]['fileSize'] = round($value['fileSize'] / 1000000, 3) . 'MB';
            }
            else
                $data[$index]['fileSize'] = '-';
            
            if(isset($value['ownerNames']) && count($value['ownerNames']) > 0)
                $data[$index]['ownerNames'] = implode(' ,', $value['ownerNames']);
            else
                $data[$index]['ownerNames'] = '-';
        }

        return $data;
    }
}
