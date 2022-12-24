<?php

declare(strict_types=1);

require('vendor/autoload.php');

use Illuminate\Contracts\Database\Eloquent\Castable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\SQLiteConnection;
use Illuminate\Database\ConnectionResolver;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;

final class Euro implements Castable {
    public string $value;

    public function __construct(string $value) {
        $this->value = $value;
    }

    public static function castUsing(array $arguments) {
        return EuroCaster::class;
    }

    public function increment() {
        die('this function is never called, but needs to be here so that EuroCaster->increment is called');
    }

    public function decrement() {
        die('this function is never called, but needs to be here so that EuroCaster->increment is called');
    }
}

final class EuroCaster implements CastsAttributes {
    public function get($model, $key, $value, $attributes) {
        return new Euro($value);
    }

    public function set($model, $key, $value, $attributes) {
        return $value->value;
    }

    public function increment($model, $key, string $value, $attributes) {
        $model->$key = new Euro(bcadd($model->$key->value, $value, 2));
        return $model->$key;
    }

    public function decrement($model, $key, string $value, $attributes) {
        $model->$key = new Euro(bcsub($model->$key->value, $value, 2));
        return $model->$key;
    }
}

final class Member extends Model {
    public $timestamps = false;
    protected $casts = [
        'amount' => Euro::class,
    ];

    public function incrementAmount(Euro $amount) {
        $this->increment('amount', $amount->value);
    }
}

$database = new ConnectionResolver(['default' => new SQLiteConnection(new \PDO('sqlite::memory:'))]);
$database->setDefaultConnection('default');
\Illuminate\Database\Eloquent\Model::setConnectionResolver($database);

$database->connection()->statement('CREATE TABLE members (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    amount DECIMAL(4, 2) NOT NULL DEFAULT 0
)');

$member = new Member();
$member->amount = new Euro('2');
$member->save();
$member->incrementAmount(new Euro('1'));

var_dump($member->amount);
