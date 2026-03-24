            import ApplicationLogo from '@/Components/ApplicationLogo';
            import Dropdown from '@/Components/Dropdown';
            import NavLink from '@/Components/NavLink';
            import ResponsiveNavLink from '@/Components/ResponsiveNavLink';
            import { Link, usePage } from '@inertiajs/react';
            import { PropsWithChildren, ReactNode, useState } from 'react';


            export default function Authenticated({
                header,
                children,
            }: PropsWithChildren<{ header?: ReactNode }>) {

                type PageProps = {
            auth: {
                user: {
                id: number
                name: string
                email: string
                role?: string
                }
            }
            }

            const adminMenuGroups = [
                {
                    title: '勤怠管理',
                    items: [
                        {
                            label: '勤怠一覧',
                            href: '/admin/attendances',
                            active: route().current('admin.attendances.*'),
                        },
                        {
                            label: '修正申請',
                            href: '/admin/attendance-corrections',
                            active: route().current('admin.attendance-corrections.*'),
                        },
                        {
                            label: '月次集計',
                            href: '/admin/reports/monthly',
                            active: route().current('admin.reports.monthly'),
                        },
                        {
                            label: '月次申請管理',
                            href: route('admin.attendance.closings.index'),
                            active: route().current('admin.attendance.closings.*'),
                        },
                        {
                            label: '賃金テーブル管理',
                            href: route('admin.wage-tables.index'),
                            active: route().current('admin.wage-tables.*'),
                        },
                        {
                            label: 'アルバイト給与確認',
                            href: route('admin.payrolls.part-time.index'),
                            active: route().current('admin.payrolls.part-time.*'),
                        },
                    ],
                },
                {
                    title: 'マスタ管理',
                    items: [
                        {
                            label: 'ユーザー管理',
                            href: route('admin.users.index'),
                            active: route().current('admin.users.*'),
                        },
                        {
                            label: '勤務ルール設定',
                            href: route('admin.work-rules.edit'),
                            active: route().current('admin.work-rules.*'),
                        },
                    ],
                },
                {
                    title: '日報管理',
                    items: [
                        {
                            label: '勤務日報管理',
                            href: route('admin.daily-reports.index'),
                            active: route().current('admin.daily-reports.*'),
                        },
                    ],
                },
            ];

            const [showingAdminDropdown, setShowingAdminDropdown] = useState(false)
            const [showingResponsiveAdminMenu, setShowingResponsiveAdminMenu] = useState(false)

                // const user = usePage().props.auth.user;
            const { auth } = usePage<PageProps>().props
            const user = auth?.user
                const isAdmin = user?.role === 'admin'
                

                const [showingNavigationDropdown, setShowingNavigationDropdown] =
                    useState(false);

                return (
                    <div className="min-h-screen bg-gray-100">
                        <nav className="border-b border-gray-100 bg-white">
                            <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                                <div className="flex h-16 justify-between">
                                    <div className="flex">
                                        <div className="flex shrink-0 items-center">
                                            <Link href="/">
                                                <ApplicationLogo className="block h-9 w-auto fill-current text-gray-800" />
                                            </Link>
                                        </div>

                                        <div className="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex">
                                            <NavLink
                                                href={route('dashboard')}
                                                active={route().current('dashboard')}
                                            >
                                                Dashboard
                                            </NavLink>
                                            <NavLink
                                                href="/attendances"
                                                active={route().current('attendances.*')}
                                                >
                                                勤怠履歴
                                            </NavLink>

                                            <NavLink href={route('daily-reports.index')} active={route().current('daily-reports.*')}>
                                            勤務日報
                                            </NavLink>



                                            {isAdmin && (
                                                <div className="relative flex items-center justify-center">
                                                    <button
                                                        type="button"
                                                        onClick={() => setShowingAdminDropdown((prev) => !prev)}
                                                        className="inline-flex items-center px-3 py-2 border-b-2 text-sm font-medium leading-5 transition duration-150 ease-in-out focus:outline-none border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300"
                                                    >
                                                        管理者メニュー
                                                        <svg
                                                            className={`ml-2 h-4 w-4 transition-transform ${showingAdminDropdown ? 'rotate-180' : ''}`}
                                                            xmlns="http://www.w3.org/2000/svg"
                                                            viewBox="0 0 20 20"
                                                            fill="currentColor"
                                                        >
                                                            <path
                                                                fillRule="evenodd"
                                                                d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.51a.75.75 0 01-1.08 0l-4.25-4.51a.75.75 0 01.02-1.06z"
                                                                clipRule="evenodd"
                                                            />
                                                        </svg>
                                                    </button>

                                                    {showingAdminDropdown && (
                                                        <div className="absolute left-1/2 top-full z-50 mt-2 w-72 -translate-x-1/2 rounded-xl bg-white p-3 shadow-lg ring-1 ring-black/5">
                                                            <div className="space-y-3">
                                                                {adminMenuGroups.map((group) => (
                                                                    <div key={group.title}>
                                                                        <div className="mb-2 px-2 text-xs font-semibold tracking-wide text-gray-400">
                                                                            {group.title}
                                                                        </div>

                                                                        <div className="grid grid-cols-2 gap-1">
                                                                            {group.items.map((item) => (
                                                                                <Link
                                                                                    key={item.label}
                                                                                    href={item.href}
                                                                                    className={`rounded-md px-3 py-2 text-sm font-medium transition ${
                                                                                        item.active
                                                                                            ? 'bg-indigo-50 text-indigo-700'
                                                                                            : 'text-gray-700 hover:bg-gray-100'
                                                                                    }`}
                                                                                >
                                                                                    {item.label}
                                                                                </Link>
                                                                            ))}
                                                                        </div>
                                                                    </div>
                                                                ))}
                                                            </div>
                                                        </div>
                                                    )}

                                                </div>
                                            )}
                                            
                                        </div>
                                    </div>

                                    <div className="hidden sm:ms-6 sm:flex sm:items-center">
                                        <div className="relative ms-3">
                                            <Dropdown>
                                                <Dropdown.Trigger>
                                                    <span className="inline-flex rounded-md">
                                                        <button
                                                            type="button"
                                                            className="inline-flex items-center rounded-md border border-transparent bg-white px-3 py-2 text-sm font-medium leading-4 text-gray-500 transition duration-150 ease-in-out hover:text-gray-700 focus:outline-none"
                                                        >
                                                            {user.name}

                                                            <svg
                                                                className="-me-0.5 ms-2 h-4 w-4"
                                                                xmlns="http://www.w3.org/2000/svg"
                                                                viewBox="0 0 20 20"
                                                                fill="currentColor"
                                                            >
                                                                <path
                                                                    fillRule="evenodd"
                                                                    d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                                                    clipRule="evenodd"
                                                                />
                                                            </svg>
                                                        </button>
                                                    </span>
                                                </Dropdown.Trigger>

                                                <Dropdown.Content>
                                                    <Dropdown.Link
                                                        href={route('profile.edit')}
                                                    >
                                                        Profile
                                                    </Dropdown.Link>
                                                    <Dropdown.Link
                                                        href={route('logout')}
                                                        method="post"
                                                        as="button"
                                                    >
                                                        Log Out
                                                    </Dropdown.Link>
                                                </Dropdown.Content>
                                            </Dropdown>
                                        </div>
                                    </div>

                                    <div className="-me-2 flex items-center sm:hidden">
                                        <button
                                            onClick={() =>
                                                setShowingNavigationDropdown(
                                                    (previousState) => !previousState,
                                                )
                                            }
                                            className="inline-flex items-center justify-center rounded-md p-2 text-gray-400 transition duration-150 ease-in-out hover:bg-gray-100 hover:text-gray-500 focus:bg-gray-100 focus:text-gray-500 focus:outline-none"
                                        >
                                            <svg
                                                className="h-6 w-6"
                                                stroke="currentColor"
                                                fill="none"
                                                viewBox="0 0 24 24"
                                            >
                                                <path
                                                    className={
                                                        !showingNavigationDropdown
                                                            ? 'inline-flex'
                                                            : 'hidden'
                                                    }
                                                    strokeLinecap="round"
                                                    strokeLinejoin="round"
                                                    strokeWidth="2"
                                                    d="M4 6h16M4 12h16M4 18h16"
                                                />
                                                <path
                                                    className={
                                                        showingNavigationDropdown
                                                            ? 'inline-flex'
                                                            : 'hidden'
                                                    }
                                                    strokeLinecap="round"
                                                    strokeLinejoin="round"
                                                    strokeWidth="2"
                                                    d="M6 18L18 6M6 6l12 12"
                                                />
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <div
                                className={
                                    (showingNavigationDropdown ? 'block' : 'hidden') +
                                    ' sm:hidden'
                                }
                            >
                                <div className="space-y-1 pb-3 pt-2">
                                    <ResponsiveNavLink
                                        href={route('dashboard')}
                                        active={route().current('dashboard')}
                                    >
                                        Dashboard
                                    </ResponsiveNavLink>

                                    {isAdmin && (
                                        <div className="border-t border-gray-200 pt-2 pb-3">
                                            <button
                                                type="button"
                                                onClick={() => setShowingResponsiveAdminMenu((prev) => !prev)}
                                                className="w-full flex items-center justify-between px-4 py-2 text-left text-sm font-medium text-gray-700"
                                            >
                                                <span>管理者メニュー</span>
                                                <svg
                                                    className={`h-4 w-4 transition-transform ${showingResponsiveAdminMenu ? 'rotate-180' : ''}`}
                                                    xmlns="http://www.w3.org/2000/svg"
                                                    viewBox="0 0 20 20"
                                                    fill="currentColor"
                                                >
                                                    <path
                                                        fillRule="evenodd"
                                                        d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.51a.75.75 0 01-1.08 0l-4.25-4.51a.75.75 0 01.02-1.06z"
                                                        clipRule="evenodd"
                                                    />
                                                </svg>
                                            </button>

  
                                            {showingResponsiveAdminMenu && (
                                                <div className="mt-2 space-y-3 px-2">
                                                    {adminMenuGroups.map((group) => (
                                                        <div key={group.title}>
                                                            <div className="px-2 pb-1 text-xs font-semibold tracking-wide text-gray-400">
                                                                {group.title}
                                                            </div>

                                                            <div className="space-y-1">
                                                                {group.items.map((item) => (
                                                                    <ResponsiveNavLink
                                                                        key={item.label}
                                                                        href={item.href}
                                                                        active={item.active}
                                                                    >
                                                                        {item.label}
                                                                    </ResponsiveNavLink>
                                                                ))}
                                                            </div>
                                                        </div>
                                                    ))}
                                                </div>
                                            )}
                                        </div>
                                    )}

                                </div>

                                <div className="border-t border-gray-200 pb-1 pt-4">
                                    <div className="px-4">
                                        <div className="text-base font-medium text-gray-800">
                                            {user.name}
                                        </div>
                                        <div className="text-sm font-medium text-gray-500">
                                            {user.email}
                                        </div>
                                    </div>

                                    <div className="mt-3 space-y-1">
                                        <ResponsiveNavLink href={route('profile.edit')}>
                                            Profile
                                        </ResponsiveNavLink>
                                        <ResponsiveNavLink
                                            method="post"
                                            href={route('logout')}
                                            as="button"
                                        >
                                            Log Out
                                        </ResponsiveNavLink>
                                    </div>
                                </div>
                            </div>
                        </nav>

                        {header && (
                            <header className="bg-white shadow">
                                <div className="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                                    {header}
                                </div>
                            </header>
                        )}

                        <main>{children}</main>
                    </div>
                );
            }
