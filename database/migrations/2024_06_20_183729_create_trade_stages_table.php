<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Ramsey\Uuid\Uuid;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('trade_stages', function (Blueprint $table) {
            $table->id();
            $table->string('stage_title', 255);
            $table->foreignId('next_step_id')->nullable()->constrained('trade_stages');
            $table->foreignId('previous_step_id')->nullable()->constrained('trade_stages');
            $table->integer('steporder');
            $table->string('yes_action', 100);
            $table->string('no_action', 100);
            $table->string('action_done_by', 20)->nullable();
            $table->boolean('deleted')->default(false);
            $table->uuid('uid')->unique();
            $table->timestamps();
        });
        $ulid = Uuid::uuid1();

        $stages = [
            [
                'stage_title' => 'Verification of product',
                'steporder' => 3,
                'yes_action' => 'MOVE_TO_NEXT_STEP',
                'no_action' => 'CANCEL_TRADE',
                'action_done_by' => 'BUYER',
                'deleted' => false,
                'next_step_id' => 3,
                'previous_step_id' => null,
                'uid' => Uuid::uuid1()->toString(),
                'created_at' => '2023-04-07 09:18:16',
                'updated_at' => '2023-04-07 09:23:45'
            ],
            [
                'stage_title' => 'Approve product',
                'steporder' => 4,
                'yes_action' => 'MOVE_TO_NEXT_STEP',
                'no_action' => 'MOVE_TO_PREV_STEP',
                'action_done_by' => 'BUYER',
                'deleted' => false,
                'next_step_id' => 4,
                'previous_step_id' => 2,
                'uid' => Uuid::uuid1()->toString(),
                'created_at' => '2023-04-08 08:17:36',
                'updated_at' => '2023-04-08 08:18:54'
            ],
            [
                'stage_title' => 'Seller delivery confirmation',
                'steporder' => 6,
                'yes_action' => 'END_TRADE',
                'no_action' => 'MOVE_TO_PREV_STEP',
                'action_done_by' => 'SELLER',
                'deleted' => false,
                'next_step_id' => null,
                'previous_step_id' => null,
                'uid' => Uuid::uuid1()->toString(),
                'created_at' => '2023-04-08 08:17:36',
                'updated_at' => '2023-04-08 08:18:54'
            ],
            [
                'stage_title' => 'Commitment',
                'steporder' => 4,
                'yes_action' => 'MOVE_TO_NEXT_STEP',
                'no_action' => 'MOVE_TO_PREV_STEP',
                'action_done_by' => 'SELLER',
                'deleted' => false,
                'next_step_id' => 8,
                'previous_step_id' => 7,
                'uid' => Uuid::uuid1()->toString(),
                'created_at' => '2023-04-08 08:17:36',
                'updated_at' => '2023-04-08 08:18:54'
            ],
            [
                'stage_title' => 'Product reception and verification',
                'steporder' => 2,
                'yes_action' => 'MOVE_TO_NEXT_STEP',
                'no_action' => 'MOVE_TO_PREV_STEP',
                'action_done_by' => 'SELLER',
                'deleted' => false,
                'next_step_id' => 3,
                'previous_step_id' => null,
                'uid' => Uuid::uuid1()->toString(),
                'created_at' => '2023-04-08 08:17:36',
                'updated_at' => '2023-04-08 08:18:54'
            ],
            [
                'stage_title' => 'Approve product',
                'steporder' => 3,
                'yes_action' => 'MOVE_TO_NEXT_STEP',
                'no_action' => 'MOVE_TO_PREV_STEP',
                'action_done_by' => 'SELLER',
                'deleted' => false,
                'next_step_id' => 5,
                'previous_step_id' => 2,
                'uid' => Uuid::uuid1()->toString(),
                'created_at' => '2023-04-08 08:17:36',
                'updated_at' => '2023-04-08 08:18:54'
            ],
            [
                'stage_title' => 'Agent Delivery confirmation',
                'steporder' => 5,
                'yes_action' => 'END_TRADE',
                'no_action' => 'MOVE_TO_PREV_STEP',
                'action_done_by' => 'SELLER',
                'deleted' => false,
                'next_step_id' => null,
                'previous_step_id' => 5,
                'uid' => Uuid::uuid1()->toString(),
                'created_at' => '2023-04-08 08:17:36',
                'updated_at' => '2023-04-08 08:18:54'
            ],
            [
                'stage_title' => 'Product waiting for an agent in your area',
                'steporder' => 1,
                'yes_action' => 'MOVE_TO_NEXT_STEP',
                'no_action' => 'CANCEL_TRADE',
                'action_done_by' => 'SELLER',
                'deleted' => false,
                'next_step_id' => null,
                'previous_step_id' => null,
                'uid' => Uuid::uuid1()->toString(),
                'created_at' => '2023-04-08 08:17:36',
                'updated_at' => '2023-04-08 08:18:54'
            ],
            [
                'stage_title' => 'Order approved by an agent in your zone',
                'steporder' => 2,
                'yes_action' => 'MOVE_TO_NEXT_STEP',
                'no_action' => 'MOVE_TO_PREV_STEP',
                'action_done_by' => 'SELLER',
                'deleted' => false,
                'next_step_id' => null,
                'previous_step_id' => null,
                'uid' => Uuid::uuid1()->toString(),
                'created_at' => '2023-04-08 08:17:36',
                'updated_at' => '2023-04-08 08:18:54'
            ],
            [
                'stage_title' => 'Seller ready to deliver',
                'steporder' => 1,
                'yes_action' => 'MOVE_TO_NEXT_STEP',
                'no_action' => 'CANCEL_TRADE',
                'action_done_by' => 'SELLER',
                'deleted' => false,
                'next_step_id' => null,
                'previous_step_id' => null,
                'uid' => Uuid::uuid1()->toString(),
                'created_at' => '2023-04-08 08:17:36',
                'updated_at' => '2023-04-08 08:18:54'
            ],
            [
                'stage_title' => 'Product certification',
                'steporder' => 2,
                'yes_action' => 'MOVE_TO_NEXT_STEP',
                'no_action' => 'CANCEL_TRADE',
                'action_done_by' => 'SELLER',
                'deleted' => false,
                'next_step_id' => null,
                'previous_step_id' => null,
                'uid' => Uuid::uuid1()->toString(),
                'created_at' => '2023-04-08 08:17:36',
                'updated_at' => '2023-04-08 08:18:54'
            ],
            [
                'stage_title' => 'Package Recieved',
                'steporder' => 4,
                'yes_action' => 'END_TRADE',
                'no_action' => 'CANCEL_TRADE',
                'action_done_by' => 'SELLER',
                'deleted' => false,
                'next_step_id' => null,
                'previous_step_id' => null,
                'uid' => Uuid::uuid1()->toString(),
                'created_at' => '2023-04-08 08:17:36',
                'updated_at' => '2023-04-08 08:18:54'
            ],
            [
                'stage_title' => 'Package delivered',
                'steporder' => 3,
                'yes_action' => 'MOVE_TO_NEXT_STEP',
                'no_action' => 'MOVE_TO_PREV_STEP',
                'action_done_by' => 'SELLER',
                'deleted' => false,
                'next_step_id' => null,
                'previous_step_id' => null,
                'uid' => Uuid::uuid1()->toString(),
                'created_at' => '2023-04-08 08:17:36',
                'updated_at' => '2023-04-08 08:18:54'
            ],
            [
                'stage_title' => 'Certificate  of acceptance of products',
                'steporder' => 1,
                'yes_action' => 'MOVE_TO_NEXT_STEP',
                'no_action' => 'CANCEL_TRADE',
                'action_done_by' => 'SELLER',
                'deleted' => false,
                'next_step_id' => null,
                'previous_step_id' => null,
                'uid' => Uuid::uuid1()->toString(),
                'created_at' => '2023-04-08 08:17:36',
                'updated_at' => '2023-04-08 08:18:54'
            ],
        ];
    
        DB::table('trade_stages')->insert($stages);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trade_stages');
    }
};
