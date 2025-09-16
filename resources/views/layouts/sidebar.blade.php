<aside
  :class="sidebarToggle ? 'translate-x-0 lg:w-[90px]' : '-translate-x-full'"
  class="sidebar fixed left-0 top-0 z-9999 flex h-screen w-[290px] flex-col overflow-y-hidden border-r border-gray-200 bg-white px-5 dark:border-gray-800 dark:bg-black lg:static lg:translate-x-0"
>
  <!-- SIDEBAR HEADER -->
  <div
    :class="sidebarToggle ? 'justify-center' : 'justify-between'"
    class="flex items-center gap-2 pt-8 sidebar-header pb-7"
  >
    <a href="{{ route('dashboard') }}">
      <span class="logo" :class="sidebarToggle ? 'hidden' : ''">
        <img class="dark:hidden" src="{{ asset('img/logo-frontdesk.svg') }}" alt="FrontDesk" />
        <img
          class="hidden dark:block"
          src="{{ asset('img/logo-frontdesk.svg') }}"
          alt="FrontDesk"
        />
      </span>

      <img
        class="logo-icon"
        :class="sidebarToggle ? 'lg:block' : 'hidden'"
        src="{{ asset('img/logo-frontdesk.svg') }}"
        alt="FrontDesk"
      />
    </a>
  </div>
  <!-- SIDEBAR HEADER -->

  <div
    class="flex flex-col overflow-y-auto duration-300 ease-linear no-scrollbar"
  >
    <!-- Sidebar Menu -->
    <nav x-data="{selected: $persist('Dashboard')}">
      <!-- Menu Group -->
      <div>
        <h3 class="mb-4 text-xs uppercase leading-[20px] text-gray-400">
          <span
            class="menu-group-title"
            :class="sidebarToggle ? 'lg:hidden' : ''"
          >
            MENU
          </span>

          <svg
            :class="sidebarToggle ? 'lg:block hidden' : 'hidden'"
            class="mx-auto fill-current menu-group-icon"
            width="24"
            height="24"
            viewBox="0 0 24 24"
            fill="none"
            xmlns="http://www.w3.org/2000/svg"
          >
            <path
              fill-rule="evenodd"
              clip-rule="evenodd"
              d="M5.99915 10.2451C6.96564 10.2451 7.74915 11.0286 7.74915 11.9951V12.0051C7.74915 12.9716 6.96564 13.7551 5.99915 13.7551C5.03265 13.7551 4.24915 12.9716 4.24915 12.0051V11.9951C4.24915 11.0286 5.03265 10.2451 5.99915 10.2451ZM17.9991 10.2451C18.9656 10.2451 19.7491 11.0286 19.7491 11.9951V12.0051C19.7491 12.9716 18.9656 13.7551 17.9991 13.7551C17.0326 13.7551 16.2491 12.9716 16.2491 12.0051V11.9951C16.2491 11.0286 17.0326 10.2451 17.9991 10.2451ZM13.7491 11.9951C13.7491 11.0286 12.9656 10.2451 11.9991 10.2451C11.0326 10.2451 10.2491 11.0286 10.2491 11.9951V12.0051C10.2491 12.9716 11.0326 13.5051 11.9991 13.5051C12.9656 13.5051 13.7491 12.8335 13.7491 12.0051V11.9951Z"
              fill=""
            />
          </svg>
        </h3>

        <ul class="flex flex-col gap-4 mb-6">
          <!-- Menu Item Dashboard -->
          <li>
            <a
              href="{{ route('dashboard') }}"
              @click.prevent="selected = (selected === 'Dashboard' ? '':'Dashboard')"
              class="menu-item group"
              :class=" (selected === 'Dashboard') || (page === 'dashboard') ? 'menu-item-active' : 'menu-item-inactive'"
            >
              <svg
                :class="(selected === 'Dashboard') || (page === 'dashboard') ? 'menu-item-icon-active'  :'menu-item-icon-inactive'"
                width="24"
                height="24"
                viewBox="0 0 24 24"
                fill="none"
                xmlns="http://www.w3.org/2000/svg"
              >
                <path
                  fill-rule="evenodd"
                  clip-rule="evenodd"
                  d="M5.5 3.25C4.25736 3.25 3.25 4.25736 3.25 5.5V8.99998C3.25 10.2426 4.25736 11.25 5.5 11.25H9C10.2426 11.25 11.25 10.2426 11.25 8.99998V5.5C11.25 4.25736 10.2426 3.25 9 3.25H5.5ZM4.75 5.5C4.75 5.08579 5.08579 4.75 5.5 4.75H9C9.41421 4.75 9.75 5.08579 9.75 5.5V8.99998C9.75 9.41419 9.41421 9.74998 9 9.74998H5.5C5.08579 9.74998 4.75 9.41419 4.75 8.99998V5.5ZM5.5 12.75C4.25736 12.75 3.25 13.7574 3.25 15V18.5C3.25 19.7426 4.25736 20.75 5.5 20.75H9C10.2426 20.75 11.25 19.7427 11.25 18.5V15C11.25 13.7574 10.2426 12.75 9 12.75H5.5ZM4.75 15C4.75 14.5858 5.08579 14.25 5.5 14.25H9C9.41421 14.25 9.75 14.5858 9.75 15V18.5C9.75 18.9142 9.41421 19.25 9 19.25H5.5C5.08579 19.25 4.75 18.9142 4.75 18.5V15ZM12.75 5.5C12.75 4.25736 13.7574 3.25 15 3.25H18.5C19.7426 3.25 20.75 4.25736 20.75 5.5V8.99998C20.75 10.2426 19.7426 11.25 18.5 11.25H15C13.7574 11.25 12.75 10.2426 12.75 8.99998V5.5ZM15 4.75C14.5858 4.75 14.25 5.08579 14.25 5.5V8.99998C14.25 9.41419 14.5858 9.74998 15 9.74998H18.5C18.9142 9.74998 19.25 9.41419 19.25 8.99998V5.5C19.25 5.08579 18.9142 4.75 18.5 4.75H15ZM15 12.75C13.7574 12.75 12.75 13.7574 12.75 15V18.5C12.75 19.7426 13.7574 20.75 15 20.75H18.5C19.7426 20.75 20.75 19.7427 20.75 18.5V15C20.75 13.7574 19.7426 12.75 18.5 12.75H15ZM14.25 15C14.25 14.5858 14.5858 14.25 15 14.25H18.5C18.9142 14.25 19.25 14.5858 19.25 15V18.5C19.25 18.9142 18.9142 19.25 18.5 19.25H15C14.5858 19.25 14.25 18.9142 14.25 18.5V15Z"
                  fill=""
                />
              </svg>

              <span
                class="menu-item-text"
                :class="sidebarToggle ? 'lg:hidden' : ''"
              >
                Dashboard
              </span>
            </a>
          </li>
          <!-- Menu Item Dashboard -->

          <!-- Menu Item Reservas -->
          <li>
            <a
              href="{{ route('bookings.index') }}"
              @click="selected = (selected === 'Reservas' ? '':'Reservas')"
              class="menu-item group"
              :class=" (selected === 'Reservas') || (page === 'bookings') ? 'menu-item-active' : 'menu-item-inactive'"
            >
              <svg
                :class="(selected === 'Reservas') || (page === 'bookings') ? 'menu-item-icon-active'  :'menu-item-icon-inactive'"
                width="24"
                height="24"
                viewBox="0 0 24 24"
                fill="none"
                xmlns="http://www.w3.org/2000/svg"
              >
                <path
                  fill-rule="evenodd"
                  clip-rule="evenodd"
                  d="M8 2C8.41421 2 8.75 2.33579 8.75 2.75V3.75H15.25V2.75C15.25 2.33579 15.5858 2 16 2C16.4142 2 16.75 2.33579 16.75 2.75V3.75H18.5C19.7426 3.75 20.75 4.75736 20.75 6V9V19C20.75 20.2426 19.7426 21.25 18.5 21.25H5.5C4.25736 21.25 3.25 20.2426 3.25 19V9V6C3.25 4.75736 4.25736 3.75 5.5 3.75H7.25V2.75C7.25 2.33579 7.58579 2 8 2ZM8 5.25H5.5C5.08579 5.25 4.75 5.58579 4.75 6V8.25H19.25V6C19.25 5.58579 18.9142 5.25 18.5 5.25H16H8ZM19.25 9.75H4.75V19C4.75 19.4142 5.08579 19.75 5.5 19.75H18.5C18.9142 19.75 19.25 19.4142 19.25 19V9.75Z"
                  fill=""
                />
              </svg>

              <span
                class="menu-item-text"
                :class="sidebarToggle ? 'lg:hidden' : ''"
              >
                Reservas
              </span>
            </a>
          </li>
          <!-- Menu Item Reservas -->

          <!-- Menu Item Propriedades -->
          <li>
            <a
              href="{{ route('properties.index') }}"
              @click="selected = (selected === 'Propriedades' ? '':'Propriedades')"
              class="menu-item group"
              :class=" (selected === 'Propriedades') || (page === 'properties') ? 'menu-item-active' : 'menu-item-inactive'"
            >
              <svg
                :class="(selected === 'Propriedades') || (page === 'properties') ? 'menu-item-icon-active'  :'menu-item-icon-inactive'"
                width="24"
                height="24"
                viewBox="0 0 24 24"
                fill="none"
                xmlns="http://www.w3.org/2000/svg"
              >
                <path
                  fill-rule="evenodd"
                  clip-rule="evenodd"
                  d="M3.25 5.5C3.25 4.25736 4.25736 3.25 5.5 3.25H18.5C19.7426 3.25 20.75 4.25736 20.75 5.5V18.5C20.75 19.7426 19.7426 20.75 18.5 20.75H5.5C4.25736 20.75 3.25 19.7426 3.25 18.5V5.5ZM5.5 4.75C5.08579 4.75 4.75 5.08579 4.75 5.5V8.58325L19.25 8.58325V5.5C19.25 5.08579 18.9142 4.75 18.5 4.75H5.5ZM19.25 10.0833H15.416V13.9165H19.25V10.0833ZM13.916 10.0833L10.083 10.0833V13.9165L13.916 13.9165V10.0833ZM8.58301 10.0833H4.75V13.9165H8.58301V10.0833ZM4.75 18.5V15.4165H8.58301V19.25H5.5C5.08579 19.25 4.75 18.9142 4.75 18.5ZM10.083 19.25V15.4165L13.916 15.4165V19.25H10.083ZM15.416 19.25V15.4165H19.25V18.5C19.25 18.9142 18.9142 19.25 18.5 19.25H15.416Z"
                  fill=""
                />
              </svg>

              <span
                class="menu-item-text"
                :class="sidebarToggle ? 'lg:hidden' : ''"
              >
                Propriedades
              </span>
            </a>
          </li>
          <!-- Menu Item Propriedades -->

          <!-- Menu Item Canais -->
          <li>
            <a
              href="{{ route('channels.index') }}"
              @click="selected = (selected === 'Canais' ? '':'Canais')"
              class="menu-item group"
              :class=" (selected === 'Canais') || (page === 'channels') ? 'menu-item-active' : 'menu-item-inactive'"
            >
              <svg
                :class="(selected === 'Canais') || (page === 'channels') ? 'menu-item-icon-active'  :'menu-item-icon-inactive'"
                width="24"
                height="24"
                viewBox="0 0 24 24"
                fill="none"
                xmlns="http://www.w3.org/2000/svg"
              >
                <path
                  fill-rule="evenodd"
                  clip-rule="evenodd"
                  d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"
                  fill=""
                />
              </svg>

              <span
                class="menu-item-text"
                :class="sidebarToggle ? 'lg:hidden' : ''"
              >
                Canais
              </span>
            </a>
          </li>
          <!-- Menu Item Canais -->

          <!-- Menu Item Relatórios -->
          <li>
            <a
              href="{{ route('reports.index') }}"
              @click="selected = (selected === 'Relatórios' ? '':'Relatórios')"
              class="menu-item group"
              :class="(selected === 'Relatórios') || (page === 'reports') ? 'menu-item-active' : 'menu-item-inactive'"
            >
              <svg
                :class="(selected === 'Relatórios') || (page === 'reports') ? 'menu-item-icon-active'  :'menu-item-icon-inactive'"
                width="24"
                height="24"
                viewBox="0 0 24 24"
                fill="none"
                xmlns="http://www.w3.org/2000/svg"
              >
                <path
                  fill-rule="evenodd"
                  clip-rule="evenodd"
                  d="M3 3.75C3 3.33579 3.33579 3 3.75 3H20.25C20.6642 3 21 3.33579 21 3.75C21 4.16421 20.6642 4.5 20.25 4.5H3.75C3.33579 4.5 3 4.16421 3 3.75ZM3 7.5C3 7.08579 3.33579 6.75 3.75 6.75H20.25C20.6642 6.75 21 7.08579 21 7.5C21 7.91421 20.6642 8.25 20.25 8.25H3.75C3.33579 8.25 3 7.91421 3 7.5ZM3 11.25C3 10.8358 3.33579 10.5 3.75 10.5H20.25C20.6642 10.5 21 10.8358 21 11.25C21 11.6642 20.6642 12 20.25 12H3.75C3.33579 12 3 11.6642 3 11.25ZM3 15C3 14.5858 3.33579 14.25 3.75 14.25H20.25C20.6642 14.25 21 14.5858 21 15C21 15.4142 20.6642 15.75 20.25 15.75H3.75C3.33579 15.75 3 15.4142 3 15ZM3 18.75C3 18.3358 3.33579 18 3.75 18H20.25C20.6642 18 21 18.3358 21 18.75C21 19.1642 20.6642 19.5 20.25 19.5H3.75C3.33579 19.5 3 19.1642 3 18.75Z"
                  fill=""
                />
              </svg>

              <span
                class="menu-item-text"
                :class="sidebarToggle ? 'lg:hidden' : ''"
              >
                Relatórios
              </span>
            </a>
          </li>
          <!-- Menu Item Relatórios -->
        </ul>
      </div>

      <!-- Others Group -->
      <div>
        <h3 class="mb-4 text-xs uppercase leading-[20px] text-gray-400">
          <span
            class="menu-group-title"
            :class="sidebarToggle ? 'lg:hidden' : ''"
          >
            CONFIGURAÇÕES
          </span>

          <svg
            :class="sidebarToggle ? 'lg:block hidden' : 'hidden'"
            class="mx-auto fill-current menu-group-icon"
            width="24"
            height="24"
            viewBox="0 0 24 24"
            fill="none"
            xmlns="http://www.w3.org/2000/svg"
          >
            <path
              fill-rule="evenodd"
              clip-rule="evenodd"
              d="M5.99915 10.2451C6.96564 10.2451 7.74915 11.0286 7.74915 11.9951V12.0051C7.74915 12.9716 6.96564 13.7551 5.99915 13.7551C5.03265 13.7551 4.24915 12.9716 4.24915 12.0051V11.9951C4.24915 11.0286 5.03265 10.2451 5.99915 10.2451ZM17.9991 10.2451C18.9656 10.2451 19.7491 11.0286 19.7491 11.9951V12.0051C19.7491 12.9716 18.9656 13.7551 17.9991 13.7551C17.0326 13.7551 16.2491 12.9716 16.2491 12.0051V11.9951C16.2491 11.0286 17.0326 10.2451 17.9991 10.2451ZM13.7491 11.9951C13.7491 11.0286 12.9656 10.2451 11.9991 10.2451C11.0326 10.2451 10.2491 11.0286 10.2491 11.9951V12.0051C10.2491 12.9716 11.0326 13.5051 11.9991 13.5051C12.9656 13.5051 13.7491 12.8335 13.7491 12.0051V11.9951Z"
              fill=""
            />
          </svg>
        </h3>

        <ul class="flex flex-col gap-4 mb-6">
          <!-- Menu Item Configurações -->
          <li>
            <a
              href="{{ route('profile.index') }}"
              @click="selected = (selected === 'Configurações' ? '':'Configurações')"
              class="menu-item group"
              :class="(selected === 'Configurações') || (page === 'settings') ? 'menu-item-active' : 'menu-item-inactive'"
            >
              <svg
                :class="(selected === 'Configurações') || (page === 'settings') ? 'menu-item-icon-active'  :'menu-item-icon-inactive'"
                width="24"
                height="24"
                viewBox="0 0 24 24"
                fill="none"
                xmlns="http://www.w3.org/2000/svg"
              >
                <path
                  fill-rule="evenodd"
                  clip-rule="evenodd"
                  d="M12 1.5C12.4142 1.5 12.75 1.83579 12.75 2.25V3.75C12.75 4.16421 12.4142 4.5 12 4.5C11.5858 4.5 11.25 4.16421 11.25 3.75V2.25C11.25 1.83579 11.5858 1.5 12 1.5ZM12 19.5C12.4142 19.5 12.75 19.8358 12.75 20.25V21.75C12.75 22.1642 12.4142 22.5 12 22.5C11.5858 22.5 11.25 22.1642 11.25 21.75V20.25C11.25 19.8358 11.5858 19.5 12 19.5ZM20.25 12C20.6642 12 21 12.3358 21 12.75C21 13.1642 20.6642 13.5 20.25 13.5H18.75C18.3358 13.5 18 13.1642 18 12.75C18 12.3358 18.3358 12 18.75 12H20.25ZM5.25 12C5.66421 12 6 12.3358 6 12.75C6 13.1642 5.66421 13.5 5.25 13.5H3.75C3.33579 13.5 3 13.1642 3 12.75C3 12.3358 3.33579 12 3.75 12H5.25ZM18.364 5.63604C18.7545 5.24551 18.7545 4.61235 18.364 4.22183C17.9735 3.8313 17.3403 3.8313 16.9498 4.22183L15.9498 5.22183C15.5593 5.61235 15.5593 6.24551 15.9498 6.63604C16.3403 7.02656 16.9735 7.02656 17.364 6.63604L18.364 5.63604ZM7.05025 18.364C7.44078 18.7545 8.07394 18.7545 8.46447 18.364C8.85499 17.9735 8.85499 17.3403 8.46447 16.9498L7.46447 15.9498C7.07394 15.5593 6.44078 15.5593 6.05025 15.9498C5.65973 16.3403 5.65973 16.9735 6.05025 17.364L7.05025 18.364ZM18.364 18.364C18.7545 17.9735 18.7545 17.3403 18.364 16.9498C17.9735 16.5593 17.3403 16.5593 16.9498 16.9498L15.9498 17.9498C15.5593 18.3403 15.5593 18.9735 15.9498 19.364C16.3403 19.7545 16.9735 19.7545 17.364 19.364L18.364 18.364ZM7.05025 5.63604C7.44078 6.02656 8.07394 6.02656 8.46447 5.63604C8.85499 5.24551 8.85499 4.61235 8.46447 4.22183L7.46447 3.22183C7.07394 2.8313 6.44078 2.8313 6.05025 3.22183C5.65973 3.61235 5.65973 4.24551 6.05025 4.63604L7.05025 5.63604ZM12 8.25C10.2051 8.25 8.75 9.70507 8.75 11.5C8.75 13.2949 10.2051 14.75 12 14.75C13.7949 14.75 15.25 13.2949 15.25 11.5C15.25 9.70507 13.7949 8.25 12 8.25ZM7.25 11.5C7.25 8.87665 9.37665 6.75 12 6.75C14.6234 6.75 16.75 8.87665 16.75 11.5C16.75 14.1234 14.6234 16.25 12 16.25C9.37665 16.25 7.25 14.1234 7.25 11.5Z"
                  fill=""
                />
              </svg>

              <span
                class="menu-item-text"
                :class="sidebarToggle ? 'lg:hidden' : ''"
              >
                Configurações
              </span>
            </a>
          </li>
          <!-- Menu Item Configurações -->
        </ul>
      </div>
    </nav>
    <!-- Sidebar Menu -->

    <!-- Promo Box -->
    <div
      :class="sidebarToggle ? 'lg:hidden' : ''"
      class="mx-auto mb-10 w-full max-w-60 rounded-2xl bg-gray-50 px-4 py-5 text-center dark:bg-white/[0.03]"
    >
      <h3 class="mb-2 font-semibold text-gray-900 dark:text-white">
        FrontDesk MVP
      </h3>
      <p class="mb-4 text-gray-500 text-theme-sm dark:text-gray-400">
        Sistema de gestão hoteleira com integração NextPax.
      </p>
      <a
        href="#"
        class="flex items-center justify-center p-3 font-medium text-white rounded-lg bg-brand-500 text-theme-sm hover:bg-brand-600"
      >
        Saiba Mais
      </a>
    </div>
    <!-- Promo Box -->
  </div>
</aside>
