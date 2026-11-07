@extends('layouts.admin')

@section('title', 'Usuarios y roles')

@section('content')
    <div class="flex flex-col gap-5">
        <div class="flex flex-col gap-4 border-b-2 border-admin-text pb-4.5 lg:flex-row lg:items-end lg:justify-between">
            <div class="flex flex-col gap-1.5">
                <p class="text-xs font-extrabold uppercase tracking-[2px] text-admin-accent">Configuración / Usuarios y roles</p>
                <h1 class="font-admin-display text-[32px] font-black uppercase leading-[1.05] text-admin-text sm:text-[46px] sm:leading-11.5">Usuarios y roles</h1>
                <p class="max-w-160 text-sm text-admin-text-secondary">Quién puede entrar al panel y qué puede hacer.</p>
            </div>
            @if ($tab === 'usuarios')
                <button type="button" data-user-form-toggle class="flex shrink-0 items-center gap-2 bg-admin-navy px-5 py-3 text-xs font-extrabold uppercase tracking-[1px] text-white">
                    <img src="{{ asset('images/admin/icon-plus-white.svg') }}" class="size-4" alt="">
                    Invitar usuario
                </button>
            @else
                <button type="button" data-role-form-toggle class="flex shrink-0 items-center gap-2 bg-admin-navy px-5 py-3 text-xs font-extrabold uppercase tracking-[1px] text-white">
                    <img src="{{ asset('images/admin/icon-plus-white.svg') }}" class="size-4" alt="">
                    Nuevo rol
                </button>
            @endif
        </div>

        @if (session('status'))
            <div class="rounded-sm border border-admin-success bg-admin-success-soft px-4 py-3 text-sm font-semibold text-admin-success">{{ session('status') }}</div>
        @endif
        @if (session('error'))
            <div class="rounded-sm border border-admin-danger bg-admin-danger-soft px-4 py-3 text-sm font-semibold text-admin-danger">{{ session('error') }}</div>
        @endif
        @if ($inviteLink)
            <div class="flex flex-col gap-2 rounded-sm border border-admin-accent bg-admin-accent-soft px-4 py-3">
                <p class="text-sm font-semibold text-admin-text">Comparte este enlace con la persona invitada (vence en 60 minutos):</p>
                <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                    <input type="text" readonly value="{{ $inviteLink }}" onclick="this.select()" class="h-10 flex-1 rounded-sm border border-admin-border-strong bg-admin-surface px-3 text-sm text-admin-text outline-none">
                </div>
            </div>
        @endif

        <div class="flex gap-1 border-b border-admin-border">
            <a href="{{ route('admin.usuarios.index') }}" class="border-b-2 px-4 py-3 text-xs font-extrabold uppercase tracking-[1px] {{ $tab === 'usuarios' ? 'border-admin-navy text-admin-text' : 'border-transparent text-admin-text-muted' }}">
                Usuarios ({{ $users->count() }})
            </a>
            <a href="{{ route('admin.usuarios.index', ['tab' => 'roles']) }}" class="border-b-2 px-4 py-3 text-xs font-extrabold uppercase tracking-[1px] {{ $tab === 'roles' ? 'border-admin-navy text-admin-text' : 'border-transparent text-admin-text-muted' }}">
                Roles ({{ $roles->count() }})
            </a>
        </div>

        @if ($tab === 'usuarios')
            <section data-user-form class="hidden flex flex-col gap-4 rounded-sm border border-admin-border bg-admin-surface p-6">
                <h2 class="font-admin-display text-xl font-black uppercase leading-none text-admin-text">Invitar usuario</h2>
                <form action="{{ route('admin.usuarios.store') }}" method="POST" class="flex flex-col gap-4">
                    @csrf
                    <div class="grid gap-4 md:grid-cols-2">
                        <label class="block">
                            <span class="mb-1.5 block text-xs font-extrabold uppercase tracking-[1px] text-admin-text-secondary">Nombre</span>
                            <input type="text" name="name" required class="h-10 w-full rounded-sm border border-admin-border-strong bg-admin-surface px-3 text-sm text-admin-text outline-none">
                        </label>
                        <label class="block">
                            <span class="mb-1.5 block text-xs font-extrabold uppercase tracking-[1px] text-admin-text-secondary">Correo</span>
                            <input type="email" name="email" required class="h-10 w-full rounded-sm border border-admin-border-strong bg-admin-surface px-3 text-sm text-admin-text outline-none">
                        </label>
                        <label class="block">
                            <span class="mb-1.5 block text-xs font-extrabold uppercase tracking-[1px] text-admin-text-secondary">Rol</span>
                            <select name="role_id" required class="h-10 w-full rounded-sm border border-admin-border-strong bg-admin-surface px-3 text-sm text-admin-text outline-none">
                                <option value="">Selecciona un rol</option>
                                @foreach ($roles as $role)
                                    <option value="{{ $role->id }}">{{ $role->name }}</option>
                                @endforeach
                            </select>
                        </label>
                    </div>
                    <div class="flex flex-col gap-2">
                        <label class="flex items-center gap-2">
                            <input type="checkbox" name="all_brands_access" value="1" data-user-all-brands class="size-4">
                            <span class="text-sm font-semibold text-admin-text">Acceso a todas las marcas</span>
                        </label>
                        <div data-user-brand-list class="flex flex-wrap gap-4 pl-6">
                            @foreach ($brands as $brand)
                                <label class="flex items-center gap-2">
                                    <input type="checkbox" name="brand_ids[]" value="{{ $brand->id }}" class="size-4">
                                    <span class="text-sm text-admin-text">{{ $brand->name }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                    <div class="flex items-center gap-3 justify-end">
                        <button type="button" data-user-form-toggle class="border border-admin-border-strong bg-admin-surface px-5 py-3 text-xs font-extrabold uppercase tracking-[1px] text-admin-text">Cancelar</button>
                        <button type="submit" class="bg-admin-navy px-5 py-3 text-xs font-extrabold uppercase tracking-[1px] text-white">Enviar invitación</button>
                    </div>
                </form>
            </section>

            <div class="flex flex-col rounded-sm border border-admin-border bg-admin-surface">
                <form action="{{ route('admin.usuarios.index') }}" method="GET" class="flex flex-col gap-3 px-5 py-4 lg:flex-row lg:items-center">
                    <div class="flex h-10 flex-1 items-center gap-2 rounded-sm border border-admin-border-strong bg-admin-surface px-3">
                        <button type="submit" class="shrink-0" aria-label="Buscar">
                            <img src="{{ asset('images/admin/icon-search.svg') }}" class="size-4" alt="">
                        </button>
                        <input type="search" name="search" value="{{ $search }}" placeholder="Buscar por nombre o correo" autocomplete="off" class="w-full text-sm font-medium text-admin-text outline-none placeholder:text-admin-text-muted">
                    </div>
                    <select name="role" onchange="this.form.submit()" class="h-10 w-full shrink-0 rounded-sm border border-admin-border-strong bg-admin-surface px-3 text-sm font-medium text-admin-text-muted outline-none lg:w-45">
                        <option value="">Rol: todos</option>
                        @foreach ($roles as $role)
                            <option value="{{ $role->id }}" @selected((string) $roleId === (string) $role->id)>{{ $role->name }}</option>
                        @endforeach
                    </select>
                    <select name="estado" onchange="this.form.submit()" class="h-10 w-full shrink-0 rounded-sm border border-admin-border-strong bg-admin-surface px-3 text-sm font-medium text-admin-text-muted outline-none lg:w-45">
                        <option value="">Estado: todos</option>
                        <option value="activos" @selected($estado === 'activos')>Activos</option>
                        <option value="invitados" @selected($estado === 'invitados')>Invitación enviada</option>
                        <option value="inactivos" @selected($estado === 'inactivos')>Inactivos</option>
                    </select>
                </form>

                <div class="overflow-x-auto">
                    <div class="flex min-w-220 items-center gap-0 border-y border-admin-border bg-admin-subtle px-4 py-2.5">
                        <div class="w-60 text-[11px] font-extrabold uppercase tracking-[1px] text-admin-text-secondary">Usuario</div>
                        <div class="w-35 text-[11px] font-extrabold uppercase tracking-[1px] text-admin-text-secondary">Rol</div>
                        <div class="w-50 text-[11px] font-extrabold uppercase tracking-[1px] text-admin-text-secondary">Acceso a marcas</div>
                        <div class="w-32 text-[11px] font-extrabold uppercase tracking-[1px] text-admin-text-secondary">Último acceso</div>
                        <div class="w-35 text-[11px] font-extrabold uppercase tracking-[1px] text-admin-text-secondary">Estado</div>
                        <div class="w-24"></div>
                    </div>

                    @forelse ($users as $row)
                        <div class="flex min-w-220 items-center gap-0 border-b border-admin-border px-4 py-3.5">
                            <div class="w-60 pr-2">
                                <p class="truncate text-sm font-bold text-admin-text">{{ $row->name }}{{ $row->is(auth()->user()) ? ' (tú)' : '' }}</p>
                                <p class="truncate text-xs text-admin-text-muted">{{ $row->email }}</p>
                            </div>
                            <div class="w-35 truncate pr-2 text-sm text-admin-text">{{ $row->role?->name ?? '—' }}</div>
                            <div class="w-50 truncate pr-2 text-sm text-admin-text-secondary">{{ $row->brandAccessLabel() }}</div>
                            <div class="w-32 pr-2 text-sm text-admin-text-secondary">{{ $row->lastAccessLabel() }}</div>
                            <div class="w-35">
                                @if ($row->isPendingInvite())
                                    <x-admin.badge tone="info" label="Invitación enviada" />
                                @elseif ($row->status)
                                    <x-admin.badge tone="success" label="Activo" />
                                @else
                                    <x-admin.badge tone="neutral" label="Inactivo" />
                                @endif
                            </div>
                            <div class="flex w-24 justify-end gap-2">
                                @if ($row->isPendingInvite())
                                    <form action="{{ route('admin.usuarios.resend', $row) }}" method="POST">
                                        @csrf
                                        <button type="submit" class="text-xs font-extrabold uppercase tracking-[1px] text-admin-accent hover:underline">Reenviar</button>
                                    </form>
                                @endif
                                <button type="button" data-user-edit-toggle="user-edit-{{ $row->id }}" class="rounded p-1 text-admin-text-secondary hover:bg-admin-hover" title="Editar">
                                    <img src="{{ asset('images/admin/icon-more.svg') }}" class="size-5" alt="Editar">
                                </button>
                            </div>
                        </div>
                        <div id="user-edit-{{ $row->id }}" class="hidden border-b border-admin-border bg-admin-subtle px-4 py-4">
                            <form action="{{ route('admin.usuarios.access', $row) }}" method="POST" class="flex flex-col gap-3">
                                @csrf @method('PATCH')
                                <div class="grid gap-3 md:grid-cols-2">
                                    <select name="role_id" class="h-9 rounded-sm border border-admin-border-strong bg-admin-surface px-2.5 text-sm text-admin-text outline-none">
                                        @foreach ($roles as $role)
                                            <option value="{{ $role->id }}" @selected($row->role_id === $role->id)>{{ $role->name }}</option>
                                        @endforeach
                                    </select>
                                    <label class="flex items-center gap-2">
                                        <input type="checkbox" name="all_brands_access" value="1" data-user-all-brands-edit="{{ $row->id }}" @checked($row->all_brands_access) class="size-4">
                                        <span class="text-sm text-admin-text">Acceso a todas las marcas</span>
                                    </label>
                                </div>
                                <div data-user-brand-list-edit="{{ $row->id }}" class="flex flex-wrap gap-4">
                                    @foreach ($brands as $brand)
                                        <label class="flex items-center gap-2">
                                            <input type="checkbox" name="brand_ids[]" value="{{ $brand->id }}" @checked($row->brands->contains('id', $brand->id)) class="size-4">
                                            <span class="text-sm text-admin-text">{{ $brand->name }}</span>
                                        </label>
                                    @endforeach
                                </div>
                                <div class="flex items-center gap-2">
                                    <button type="submit" class="bg-admin-navy px-4 py-2 text-xs font-extrabold uppercase tracking-[1px] text-white">Guardar acceso</button>
                                    <button type="button" data-user-edit-toggle="user-edit-{{ $row->id }}" class="border border-admin-border-strong bg-admin-surface px-4 py-2 text-xs font-extrabold uppercase tracking-[1px] text-admin-text">Cancelar</button>
                                </div>
                            </form>
                            <div class="mt-3 flex gap-2 border-t border-admin-border pt-3">
                                <form action="{{ route('admin.usuarios.toggle', $row) }}" method="POST">
                                    @csrf @method('PATCH')
                                    <button type="submit" class="border border-admin-border-strong bg-admin-surface px-4 py-2 text-xs font-extrabold uppercase tracking-[1px] text-admin-text">{{ $row->status ? 'Desactivar' : 'Activar' }}</button>
                                </form>
                                <form action="{{ route('admin.usuarios.destroy', $row) }}" method="POST" onsubmit="return confirm('¿Eliminar este usuario?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="border border-admin-danger px-4 py-2 text-xs font-extrabold uppercase tracking-[1px] text-admin-danger">Eliminar</button>
                                </form>
                            </div>
                        </div>
                    @empty
                        <div class="px-4 py-10 text-center text-sm text-admin-text-muted">No hay usuarios que coincidan con el filtro.</div>
                    @endforelse
                </div>
            </div>
        @else
            <section data-role-form class="hidden flex flex-col gap-4 rounded-sm border border-admin-border bg-admin-surface p-6">
                <h2 class="font-admin-display text-xl font-black uppercase leading-none text-admin-text">Nuevo rol</h2>
                <form action="{{ route('admin.roles.store') }}" method="POST" class="flex items-end gap-3">
                    @csrf
                    <label class="block flex-1">
                        <span class="mb-1.5 block text-xs font-extrabold uppercase tracking-[1px] text-admin-text-secondary">Nombre del rol</span>
                        <input type="text" name="name" required placeholder="Ej. Ventas, Contenido" class="h-10 w-full rounded-sm border border-admin-border-strong bg-admin-surface px-3 text-sm text-admin-text outline-none">
                    </label>
                    <button type="submit" class="bg-admin-navy px-5 py-3 text-xs font-extrabold uppercase tracking-[1px] text-white">Guardar rol</button>
                    <button type="button" data-role-form-toggle class="border border-admin-border-strong bg-admin-surface px-5 py-3 text-xs font-extrabold uppercase tracking-[1px] text-admin-text">Cancelar</button>
                </form>
            </section>

            <div class="flex flex-col rounded-sm border border-admin-border bg-admin-surface">
                <div class="flex items-center gap-0 border-b border-admin-border bg-admin-subtle px-4 py-2.5">
                    <div class="flex-1 text-[11px] font-extrabold uppercase tracking-[1px] text-admin-text-secondary">Rol</div>
                    <div class="w-32 text-right text-[11px] font-extrabold uppercase tracking-[1px] text-admin-text-secondary">Usuarios</div>
                    <div class="w-40"></div>
                </div>
                @foreach ($roles as $role)
                    <div class="flex items-center gap-0 border-b border-admin-border px-4 py-3.5 last:border-b-0">
                        <form action="{{ route('admin.roles.update', $role) }}" method="POST" class="flex flex-1 items-center gap-2">
                            @csrf @method('PATCH')
                            <input type="text" name="name" value="{{ $role->name }}" class="h-9 w-full max-w-70 rounded-sm border border-transparent bg-transparent px-2 text-sm font-bold text-admin-text outline-none focus:border-admin-border-strong focus:bg-admin-surface">
                            <button type="submit" class="text-xs font-extrabold uppercase tracking-[1px] text-admin-accent hover:underline">Guardar</button>
                        </form>
                        <div class="w-32 text-right text-sm font-bold text-admin-text">{{ $role->users_count }}</div>
                        <div class="flex w-40 justify-end">
                            <form action="{{ route('admin.roles.destroy', $role) }}" method="POST" onsubmit="return confirm('¿Eliminar este rol?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="text-xs font-extrabold uppercase tracking-[1px] text-admin-danger hover:underline">Eliminar</button>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
@endsection
