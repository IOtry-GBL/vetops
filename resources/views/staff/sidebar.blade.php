<!-- Staff Sidebar Navigation -->
<a href="{{ route('staff.dashboard') }}" class="sidebar-item {{ request()->routeIs('staff.dashboard') ? 'active' : '' }}">Dashboard</a>
<a href="{{ route('staff.queue') }}" class="sidebar-item {{ request()->routeIs('staff.queue') ? 'active' : '' }}">Appointment Queue</a>
<a href="{{ route('staff.clients') }}" class="sidebar-item {{ request()->routeIs('staff.clients*') ? 'active' : '' }}">Clients</a>
<a href="{{ route('staff.pets') }}" class="sidebar-item {{ request()->routeIs('staff.pets*') ? 'active' : '' }}">Pets</a>
<a href="{{ route('staff.prescriptions') }}" class="sidebar-item {{ request()->routeIs('staff.prescriptions*') ? 'active' : '' }}">Prescriptions</a>
<a href="{{ route('staff.medical-records') }}" class="sidebar-item {{ request()->routeIs('staff.medical-records*') ? 'active' : '' }}">Medical Records</a>
<a href="{{ route('staff.reports') }}" class="sidebar-item {{ request()->routeIs('staff.reports*') ? 'active' : '' }}">Reports</a>
<a href="{{ route('staff.notifications') }}" class="sidebar-item {{ request()->routeIs('staff.notifications') ? 'active' : '' }}">Notifications</a>
