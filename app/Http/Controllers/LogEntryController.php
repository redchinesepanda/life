<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Validator;
use App\Models\LogEntry;
use Carbon\Carbon;
use DB;
use Illuminate\Support\Facades\Cache;

/**
 * [review] Общие замечания.
 *  Логику получения и добавления лучше выносить в отдельные сервисы/пакеты/репозитории/и т.д. В общем , любым проектным шаблоном.
 *  Ответы не стандартизированы, что усложняет работу для клиентов.
 *  Если будет много данных, то сервер может и не вернуть или повиснуть. Желательно добвалять пагинацию
 */
class LogEntryController extends Controller
{
    /**
     * count url LogEntry with user limit
     *
     * @return json
     */
    public function custom( $from, $to ) {
        // [review] Формат дат лучше провалидировать иначе 500 упадет.
        $startDate = Carbon::createFromFormat('Y-m-d', $from)->startOfDay();
        
        $endDate = Carbon::createFromFormat('Y-m-d', $to)->endOfDay();

        return response()->json( [
            'data' => LogEntry::select('url', DB::raw('count(*) as total'))
                ->groupBy('url')
                ->whereBetween(
                    'created_at',
                    [$startDate, $endDate]
                )
                ->get()
        ], 200 );
    }

    /**
     * count url LogEntry with year limit
     *
     * @return json
     */
    private function year( $url ) {
        return response()->json( [
            'data' => LogEntry::where( 'url', $url )
                ->whereBetween(
                    'created_at',
                    [ now()->startOfYear(), now()->endOfYear() ]
                )
                ->count()
        ], 200 );
    }

    /**
     * count url LogEntry with month limit
     *
     * @return json
     */
    private function month( $url ) {
        return response()->json( [
            'data' => LogEntry::where( 'url', $url )
                ->whereBetween(
                    'created_at',
                    [ now()->startOfMonth(), now()->endOfMonth() ]
                )
                ->count()
        ], 200 );
    }

    /**
     * count url LogEntry with week limit
     *
     * @return json
     */
    private function week( $url ) {
        return response()->json( [
            'data' => LogEntry::where( 'url', $url )
                ->whereBetween(
                    'created_at',
                    [ now()->startOfWeek(), now()->endOfWeek() ]
                )
                ->count()
        ], 200 );
    }

    /**
     * count url LogEntry with day limit
     *
     * @return json
     */
    private function day( $url ) {
        return response()->json( [
            'data' => LogEntry::where( 'url', $url )
                ->whereDate( 'created_at', now()->today() )
                ->count()
        ], 200 );
    }

    /**
     * count url LogEntry with period limit
     *
     * @return json
     */
    public function period(Request $request)
    {
        $input = $request->only( [ 'url', 'period' ] );

        $validator = Validator::make(
            $input,
            [
                'url' => 'required|url',

                'period' => 'required|string'
            ]
        );

        if ( $validator->fails() ) {
            return response()->json( [
                'success' => false,

                'message' => 'Change request data as described below.',

                'errors' => $validator->errors()
            ] );
        }

        // [review] Лучше проверку типа внести в логику валидатора
        // Или хотя бы отдавать 400 код, если неправильный период.
        // Иначе получается 200 код, но запрос не выполнен.
        $response = response()->json( [
            'success' => false,

            'message' => 'Use correct period value (day, week, month, year).'
        ] );

        if ( $input['period'] == 'day' ) {
            $response = $this->day( $input['url'] );
        }

        if ( $input['period'] == 'week' ) {
            $response = $this->week( $input['url'] );
        }

        if ( $input['period'] == 'month' ) {
            $response = $this->month( $input['url'] );
        }

        if ( $input['period'] == 'year' ) {
            $response = $this->year( $input['url'] );
        }

        return $response;
    }

    /**
     * count url LogEntry
     *
     * @return json
     */
    public function state(Request $request)
    {
        $input = $request->only( [ 'url' ] );

        $validator = Validator::make(
            $input,
            [ 'url' => 'required|url' ]
        );

        if ( $validator->fails() ) {
            return response()->json( [
                'success' => false,

                'message' => 'Change request data as described below.',

                'errors' => $validator->errors()
            ] );
        }

        $result['cache'] = 'cache unused';

        if ( Cache::store( 'redis' )->has( $input['url'] ) ) {
            // [review] из кэша возвращается string
            $result['data'] = Cache::store( 'redis' )->get( $input['url'] );

            $result['cache'] = 'from cache';
		} else {
            // [review] из БД возвращается int
            $result['data'] = LogEntry::where( 'url', $input['url'] )->count();

			Cache::store( 'redis' )->put(
                $input['url'],

                $result['data'],

                now()->addMinutes( 10 )
            );

            $result['cache'] = 'cache set';
        }

        // [review] в апи уходит то int, то string. 
        return response()->json( $result, 200 );
    }

    /**
     * new LogEntry
     *
     * @return json
     */
    public function new(Request $request)
    {
        $input = $request->only( [ 'ip', 'url' ] );

        // [review] ip нужно брать из заголовков HTTP_*, X-Proxy-Ip-pass и т.д.
        // Напрямую можно все что угодно передать. К тому же напрямую js-клиент ничего не знает об ip адресе
        // https://test.ru/main?utm_time=1235 и https://test.ru/main одинаковые или разные страницы?
        $validator = Validator::make(
            $input,
            [
                'ip' => 'required|ip',

                'url' => 'required|url'
            ]
        );

        if ( $validator->fails() ) {
            return response()->json( [
                'success' => false,

                'message' => 'Change request data as described below.',

                'errors' => $validator->errors()
            ] );
        }

        $logEntry = new LogEntry;
 
        $logEntry->ip = $input['ip'];

        $logEntry->url = $input['url'];
 
        $logEntry->save();

        $cache_state = 'cache unused';

        /**
         * [review] т.к. это ресурс на добавление, то нет смысла проверять есть ли он в кеше, т.к. считаем каждый просмотр.
         * По логике контроллера в кэш попадет урл только тот, который вызывали через метод /state
         */
        if ( Cache::store( 'redis' )->has( $input['url'] ) ) {
            $cahe_value = Cache::store( 'redis' )->get( $input['url'] );

			Cache::store( 'redis' )->put(
                $input['url'],

                ++$cahe_value,

                now()->addMinutes( 10 )
            );

            $cache_state = 'cache set';
		}

        return response()->json( [
            'success' => true,

            'message' => 'LogEntry added succesfully.',

            'data' => $logEntry,

            'cache' => $cache_state
        ], 200 );
    }
}
