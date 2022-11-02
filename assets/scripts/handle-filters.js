const year_range = document.querySelector('#year-of-manufacture');
const price_range = document.querySelector('#price');
const year_none = document.querySelector('#year-none');
const price_none = document.querySelector('#price-none');

year_range.addEventListener('change', (e) => {
    document.querySelector('#year-indicator').textContent = e.currentTarget.value;
});
price_range.addEventListener('change', (e) => {
    document.querySelector('#price-indicator').textContent = e.currentTarget.value + " zł/h";
});
year_none.addEventListener('change', (e) => {
    year_range.toggleAttribute('disabled');
});
price_none.addEventListener('change', (e) => {
    price_range.toggleAttribute('disabled');
});