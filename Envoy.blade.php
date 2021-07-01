@setup
    $server = isset($env) && $env == 'production' ? 'root@149.129.224.120' : 'root@149.129.220.30';
    $login = isset($env) && $env == 'production' ? 'su hijrah_apps' : 'su hijrah_apps';
@endsetup

@servers(['web' => $server])

@task('deploy', ['on' => 'web'])
    {{$login}}
    cd /var/apihijrah/
    @if ($branch && $branch == 'development')
        {{-- echo 'hijrahapps1234@2020' | sudo -S git pull origin {{ $branch }} --}}
        git pull origin {{ $branch }}
    @elseif ($branch && $branch == 'master')
        git pull origin {{ $branch }}
    @else
        echo 'branch tidak terdaftar'
    @endif
    composer install
@endtask