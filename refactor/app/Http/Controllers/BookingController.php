<?php

namespace DTApi\Http\Controllers;

use DTApi\Models\Job;
use DTApi\Http\Requests;
use DTApi\Models\Distance;
use Illuminate\Http\Request;
use DTApi\Repository\BookingRepository;
use Illuminate\Support\Facades\DB;

/**
 * Class BookingController
 * @package DTApi\Http\Controllers
 */
class BookingController extends Controller
{
    /**
     * BookingController constructor.
     * @param BookingRepository $bookingRepository
     */
    public function __construct(
        protected BookingRepository $repository
    ) {
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function index(Request $request)
    {
        $userId = $request->get('user_id');
        $authenticatedUserType = $request->__authenticatedUser?->user_type;

        if ($userId) {
            $response = $this->repository->getUsersJobs($userId);
        } elseif (
            $authenticatedUserType == config('filename.ADMIN_ROLE_ID') ||
            $authenticatedUserType == config('filename.SUPERADMIN_ROLE_ID')
        ) {
            $response = $this->repository->getAll($request);
        } else {
            $response = [];
        }

        return response($response);
    }

    /**
     * @param $id
     * @return mixed
     */
    public function show($id)
    {
        $job = $this->repository->with('translatorJobRel.user')->findOrFail($id);

        return response($job);
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function store(Request $request)
    {
        $authenticatedUser = $request->__authenticatedUser;

        if ($authenticatedUser?->user_type != config('filename.CUSTOMER_ROLE_ID')) {
            return response(['status' => 'fail', 'message' => 'Translator can not create booking']);
        }

        $data = $request->validate([
            'from_language_id' => ['required'],
            'immediate' => ['required', 'boolean'],
            'due_date' => ['required_if:immediate,false', 'date_format:Y-m-d'],
            'due_time' => ['required_if:immediate,false', 'date_format:H:i'],
            'customer_phone_type' => ['required_if:immediate,false', 'boolean'],
            'customer_physical_type' => ['required_if:immediate,false', 'boolean'],
            'duration' => ['required'],
            'job_for' => ['array', 'required'],
            'job_for.*' => ['required', 'min:1'],
        ]);

        $response = $this->repository->store($authenticatedUser, $data);

        return response($response);
    }

    /**
     * @param $id
     * @param Request $request
     * @return mixed
     */
    public function update($id, Request $request)
    {
        $data = $request->all();
        $cuser = $request->__authenticatedUser;
        $response = $this->repository->updateJob($id, array_except($data, ['_token', 'submit']), $cuser);

        return response($response);
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function immediateJobEmail(Request $request)
    {
        $data = $request->all();
        $response = $this->repository->storeJobEmail($data);

        return response($response);
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function getHistory(Request $request)
    {
        if ($userId = $request->get('user_id')) {
            $response = $this->repository->getUsersJobsHistory($userId, $request);
            return response($response);
        }

        return null;
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function acceptJob(Request $request)
    {
        $data = $request->all();
        $user = $request->__authenticatedUser;

        $response = $this->repository->acceptJob($data, $user);

        return response($response);
    }

    public function acceptJobWithId(Request $request)
    {
        $data = $request->get('job_id');
        $user = $request->__authenticatedUser;

        $response = $this->repository->acceptJobWithId($data, $user);

        return response($response);
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function cancelJob(Request $request)
    {
        $data = $request->all();
        $user = $request->__authenticatedUser;

        $response = $this->repository->cancelJobAjax($data, $user);

        return response($response);
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function endJob(Request $request)
    {
        $data = $request->all();
        $response = $this->repository->endJob($data);

        return response($response);
    }

    public function customerNotCall(Request $request)
    {
        $data = $request->all();
        $response = $this->repository->customerNotCall($data);

        return response($response);
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function getPotentialJobs(Request $request)
    {
        $user = $request->__authenticatedUser;
        $response = $this->repository->getPotentialJobs($user);

        return response($response);
    }

    public function distanceFeed(Request $request)
    {
        $data = $request->all();

        $distance = $data['distance'] ?? null;
        $time = $data['time'] ?? null;
        $jobid = $data['jobid'] ?? null;
        $session = $data['session_time'] ?? null;

        $isFlagged = $data['flagged'] ?? false;
        $isManuallyHandled = $data['manually_handled'] ?? false;
        $isByAdmin = $data['by_admin'] ?? false;

        $admincomment = $data['admincomment'] ?? null;

        if ($isFlagged && !$admincomment) {
            return "Please, add comment";
        }

        $flagged = $isFlagged ? 'yes' : 'no';
        $manually_handled = $isManuallyHandled ? 'yes' : 'no';
        $by_admin = $isByAdmin ? 'yes' : 'no';

        DB::transaction(function () use ($time, $distance, $jobid, $session, $flagged, $manually_handled, $by_admin, $admincomment) {
            if ($time || $distance) {
                Distance::where('job_id', '=', $jobid)
                    ->update(['distance' => $distance, 'time' => $time]);
            }

            if ($admincomment || $session || $flagged || $manually_handled || $by_admin) {
                Job::where('id', '=', $jobid)
                    ->update([
                        'admin_comments' => $admincomment,
                        'flagged' => $flagged,
                        'session_time' => $session,
                        'manually_handled' => $manually_handled,
                        'by_admin' => $by_admin
                    ]);
            }
        });

        return response('Record updated!');
    }

    public function reopen(Request $request)
    {
        $data = $request->all();
        $response = $this->repository->reopen($data);

        return response($response);
    }

    public function resendNotifications(Request $request)
    {
        $data = $request->all();
        $job = $this->repository->find($data['jobid']);
        $job_data = $this->repository->jobToData($job);
        $this->repository->sendNotificationTranslator($job, $job_data, '*');

        return response(['success' => 'Push sent']);
    }

    /**
     * Sends SMS to Translator
     * @param Request $request
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
     */
    public function resendSMSNotifications(Request $request)
    {
        $data = $request->all();
        $job = $this->repository->find($data['jobid']);
        $job_data = $this->repository->jobToData($job);

        try {
            $this->repository->sendSMSNotificationToTranslator($job);
            return response(['success' => 'SMS sent']);
        } catch (\Exception $e) {
            return response(['success' => $e->getMessage()]);
        }
    }
}
