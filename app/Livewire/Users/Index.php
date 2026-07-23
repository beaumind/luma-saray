<?php

namespace App\Livewire\Users;

use App\Models\User;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Permission\Models\Role;

#[Layout('layouts.app', ['title' => 'کاربران'])]
class Index extends Component
{
    use WithPagination;

    public string $search = '';
    public bool $showModal = false;
    public ?int $editingId = null;

    public string $name = '';
    public string $mobile = '';
    public string $password = '';
    public string $role = 'manager';
    public bool $is_active = true;

    public function openCreate(): void
    {
        $this->resetForm();
        $this->editingId = null;
        $this->showModal = true;
    }

    public function openEdit(int $id): void
    {
        $user = User::findOrFail($id);
        $this->editingId = $id;
        $this->name = $user->name;
        $this->mobile = $user->mobile;
        $this->password = '';
        $this->role = $user->roles->first()?->name ?? 'manager';
        $this->is_active = $user->is_active;
        $this->showModal = true;
    }

    public function save(): void
    {
        $rules = [
            'name' => 'required|string|max:200',
            'mobile' => 'required|string|max:20|unique:users,mobile' . ($this->editingId ? ",{$this->editingId}" : ''),
            'role' => 'required|string|exists:roles,name',
        ];

        if (!$this->editingId) {
            $rules['password'] = 'required|string|min:6';
        } else {
            $rules['password'] = 'nullable|string|min:6';
        }

        $this->validate($rules);

        $data = [
            'name' => $this->name,
            'mobile' => $this->mobile,
            'is_active' => $this->is_active,
        ];

        if ($this->password) {
            $data['password'] = $this->password;
        }

        if ($this->editingId) {
            $user = User::findOrFail($this->editingId);
            $user->update($data);
            $user->syncRoles([$this->role]);
            session()->flash('success', 'کاربر بروزرسانی شد.');
        } else {
            $user = User::create($data);
            $user->assignRole($this->role);
            session()->flash('success', 'کاربر ثبت شد.');
        }

        $this->showModal = false;
        $this->resetForm();
    }

    public function toggleActive(int $id): void
    {
        $user = User::findOrFail($id);
        if ($user->id === auth()->id()) return;
        $user->update(['is_active' => !$user->is_active]);
    }

    private function resetForm(): void
    {
        $this->name = '';
        $this->mobile = '';
        $this->password = '';
        $this->role = 'manager';
        $this->is_active = true;
        $this->resetValidation();
    }

    public function render()
    {
        $users = User::with('roles')
            ->when($this->search, fn($q) => $q->where('name', 'like', "%{$this->search}%")
                ->orWhere('mobile', 'like', "%{$this->search}%"))
            ->orderBy('name')
            ->paginate(15);

        $roles = Role::orderBy('name')->get();

        return view('livewire.users.index', compact('users', 'roles'));
    }
}
