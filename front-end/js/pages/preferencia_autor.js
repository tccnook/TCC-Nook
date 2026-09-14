const checkboxes = document.querySelectorAll('.aut-checkbox');
        const counter = document.getElementById('selected-counter');
        checkboxes.forEach(checkbox => {
            checkbox.addEventListener('change', () => {
                const total = document.querySelectorAll('.aut-checkbox:checked').length;
                if (total > 5) {
                    checkbox.checked = false;
                    alert('Você só pode selecionar 5 artistas.');
                    return;
                } 
                checkbox.closest('.aut-card').classList.toggle('selected', checkbox.checked);
                counter.textContent = `${total} de 5 selecionados`;

            });
        });