const image_input = document.querySelector('#images');
const thumnbail_container = document.querySelector('#thumbnail-container');

image_input.addEventListener('change', (e) => {
    if(e.target.files.length > 4)
    {
        alert("Maksymalnie 4 pliki...");
        e.target.value = '';
        e.preventDefault();
        return;
    }

    thumnbail_container.innerHTML = '';
    for(const file of e.target.files)
    {
        let image_thumbnail = document.createElement('img');
        image_thumbnail.classList.add('img-thumbnail', 'car-image-thumbnail', 'mr-2', 'mb-3');
        image_thumbnail.src = URL.createObjectURL(file);
        image_thumbnail.onload = () => {
            URL.revokeObjectURL(image_thumbnail.src);
        }
        thumnbail_container.appendChild(image_thumbnail);
    }
});