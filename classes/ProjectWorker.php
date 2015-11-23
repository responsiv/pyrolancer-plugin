<?php namespace Ahoy\Pyrolancer\Classes;

use Mail;
use Event;
use Ahoy\Pyrolancer\Models\Worker as WorkerModel;
use Ahoy\Pyrolancer\Models\Project as ProjectModel;
use Carbon\Carbon;
use ApplicationException;

/**
 * Worker class, engaged by the automated worker
 */
class ProjectWorker
{

    use \October\Rain\Support\Traits\Singleton;

    /**
     * @var bool There should be only one task performed per execution.
     */
    protected $isReady = true;

    /**
     * @var string Processing message
     */
    protected $logMessage = 'There are no outstanding activities to perform.';

    /*
     * Process all tasks
     */
    public function process()
    {
        $this->isReady && $this->processWorkerDigest();

        return $this->logMessage;
    }

    /**
     * This will list all workers, sorted by the last digest date,
     * where the last digest date exceeds the specified digest frequency,
     * finds jobs that were submitted between now and last digest date,
     * matches the worker profile and emails them in chunks of 100.
     */
    public function processWorkerDigest()
    {
        $days = max(1, 1); // Must always be greater than 1
        $loop = 100;

        $now = Carbon::now();
        $start = Carbon::now()->subDays($days);

        $count = 0;
        for ($i = 0; $i < $loop; $i++) {
            $worker = WorkerModel::make()
                ->where('last_digest_at', '<', $start)
                ->orWhereNull('last_digest_at')
                ->first()
            ;

            if ($worker) {
                ProjectNotify::sendDigest($worker, $worker->last_digest_at);

                $worker->last_digest_at = $now;
                $worker->timestamps = false;
                $worker->save();
                $count++;
            }
        }

        if ($count > 0) {
            $this->logActivity(sprintf(
                'Sent job digest to %s worker(s).',
                $count
            ));
        }
    }

    /**
     * Called when activity has been performed.
     */
    protected function logActivity($message)
    {
        $this->logMessage = $message;
        $this->isReady = false;
    }

}