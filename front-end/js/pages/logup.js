document.addEventListener("DOMContentLoaded", () => {
    const btnVoltar = document.querySelector(".previous-step");
    if (btnVoltar) {
        btnVoltar.addEventListener("click", prevStep);
    }
});

function nextStep() {
    const currentStepEl = document.querySelector(".step.active");
    if (!currentStepEl) return;

    const currentStep = parseInt(currentStepEl.dataset.step);
    const nextStep = currentStep + 1;

    const nextStepEl = document.querySelector(`[data-step="${nextStep}"]`);

    if (nextStepEl) {
        if (currentStep === 1) {
            const email = document.getElementById("email").value.trim();
            const nomeComp = document.getElementById("nome_comp").value.trim();
            const nomeUser = document.getElementById("nome_user").value.trim();

            if (!email || !nomeComp || !nomeUser) {
                alert("Por favor, preencha todos os campos desta etapa.");
                return;
            }
        }
        currentStepEl.classList.remove("active");
        nextStepEl.classList.add("active");
    }
}

function prevStep() {
    const currentStepEl = document.querySelector(".step.active");
    if (!currentStepEl) return;

    const currentStep = parseInt(currentStepEl.dataset.step);
    const prevStep = currentStep - 1;

    const prevStepEl = document.querySelector(`[data-step="${prevStep}"]`);

    if (prevStepEl) {
        currentStepEl.classList.remove("active");
        prevStepEl.classList.add("active");
    }
}