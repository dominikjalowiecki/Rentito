const dark_mode_switch = document.querySelector('#dark-mode-switch');
if(dark_mode_switch)
{
    const body = document.body;
    const navbar = document.querySelector('.navbar');
    const is_dark_mode_active = (localStorage.getItem('dark-mode') === 'true') ? true : false;

    dark_mode_switch.checked = is_dark_mode_active;
    darkModeClassSet(dark_mode_switch.checked);

    dark_mode_switch.addEventListener('change', (e) => {
        localStorage.setItem('dark-mode', e.target.checked);
        darkModeClassToggle();
    });

    function darkModeClassSet(is_checked)
    {
        if(is_checked)
        {
            body.classList.add('dark-mode');
            navbar.classList.add('bg-dark');
            navbar.classList.remove('bg-light');
        }
    }

    function darkModeClassToggle()
    {
        body.classList.toggle('dark-mode');
        navbar.classList.toggle('bg-dark');
        navbar.classList.toggle('bg-light');
    }
}