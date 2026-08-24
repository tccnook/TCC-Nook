const steps = document.querySelectorAll('.step');

const nextButton = document.querySelector('.next-step');
const previousButton = document.querySelector('.previous-step');

let currentStep = 1;
const totalSteps = steps.length;


function showStep(step) {

    steps.forEach((element) => {
        element.classList.remove('active');
    });

    const current = document.querySelector(
        `.step[data-step="${step}"]`
    );

    current.classList.add('active');
}


nextButton.addEventListener('click', () => {

    if (currentStep < totalSteps) {
        currentStep++;

        showStep(currentStep);
    }

});


previousButton.addEventListener('click', () => {

    if (currentStep > 1) {
        currentStep--;

        showStep(currentStep);
    }

});