<?php

namespace App\Gateway;

use Illuminate\Database\ConnectionInterface;

class UserGateway
{
    /**
     * @var ConnectionInterface
     */
    private $db;

    public function __construct()
    {
        $this->db = app('db');
    }

    // Users
    public function getUser(string $userId)
    {
        $user = $this->db->table('users')
            ->where('user_id', $userId)
            ->first();

        if ($user) {
            return (array) $user;
        }

        return null;
    }

    public function saveUser(string $userId, string $displayName)
    {
        $user = $this->db->table('users')
            ->where('user_id', $userId)
            ->first();

        if (!$user) {
            $this->db->table('users')
                ->insert([
                    'user_id' => $userId,
                    'display_name' => $displayName
                ]);
        }
    }

    // Rooms
    public function getRoom(string $roomId)
    {
        $room = $this->db->table('rooms')
            ->where('room_id', $roomId)
            ->first();

        if ($room) {
            return (array) $room;
        }

        return null;
    }

    public function saveRoom(string $roomId)
    {
        $room = $this->db->table('rooms')
            ->where('room_id', $roomId)
            ->first();

        if (!$room) {
            $this->db->table('rooms')
                ->insert([
                    'room_id' => $roomId
                ]);
        }
    }

    // Groups
    public function getGroup(string $groupId)
    {
        $group = $this->db->table('groups')
            ->where('group_id', $groupId)
            ->first();

        if ($group) {
            return (array) $group;
        }

        return null;
    }

    public function saveGroup(string $groupId)
    {
        $group = $this->db->table('groups')
            ->where('group_id', $groupId)
            ->first();

        if (!$group) {
            $this->db->table('groups')
                ->insert([
                    'group_id' => $groupId
                ]);
        }
    }
}
