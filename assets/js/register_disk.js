// Variables
let currentStep = 1;
const totalSteps = 4;
let formData = {};

// DOM Elements
const form = document.getElementById('discoForm');
const alertContainer = document.getElementById('alertContainer');
const prevBtn = document.getElementById('prevBtn');
const nextBtn = document.getElementById('nextBtn');
const submitBtn = document.getElementById('submitBtn');

// Condition descriptions
const conditionDescriptions = {
    'Mint': 'Perfeito estado, como novo, sem nenhum defeito visível',
    'E+': 'Excelente estado com sinais mínimos de uso',
    'E': 'Excelente estado com pequenos sinais de uso',
    'VG+': 'Muito bom estado com alguns sinais de uso',
    'VG': 'Muito bom estado com sinais moderados de uso',
    'G+': 'Bom estado com sinais evidentes de uso',
    'G': 'Bom estado com sinais consideráveis de uso'
};

// Initialize
document.addEventListener('DOMContentLoaded', function() {
    initializeForm();
    setupEventListeners();
    checkAuthentication();
    loadUserInfo();
});

function initializeForm() {
    updateStepVisibility();
    updateProgressIndicator();
    updateNavigationButtons();
}

function setupEventListeners() {
    // Media type selection
    setupMediaTypeSelection();
    
    // Origin selection
    setupOriginSelection();
    
    // Edition selection
    setupEditionSelection();
    
    // Condition selection
    setupConditionSelection();
    
    // Country selection for import warning
    setupCountrySelection();
    
    // Form submission
    if (form) {
        form.addEventListener('submit', handleFormSubmission);
    }
    
    // Auto-save form data
    setupAutoSave();
}

function setupMediaTypeSelection() {
    const mediaOptions = document.querySelectorAll('.media-option');
    const boxsetFields = document.getElementById('boxsetFields');
    
    mediaOptions.forEach(option => {
        option.addEventListener('click', function() {
            const radio = this.querySelector('input[type="radio"]');
            radio.checked = true;
            
            // Update visual selection
            mediaOptions.forEach(opt => opt.classList.remove('selected'));
            this.classList.add('selected');
            
            // Show/hide BoxSet fields
            if (boxsetFields) {
                if (radio.value === 'BoxSet') {
                    boxsetFields.classList.remove('hidden');
                } else {
                    boxsetFields.classList.add('hidden');
                }
            }
            
            // Update progress
            updateFormProgress();
        });
    });
}

function setupOriginSelection() {
    const originOptions = document.querySelectorAll('.origin-option');
    const importFields = document.getElementById('importadoFields');
    
    originOptions.forEach(option => {
        option.addEventListener('click', function() {
            const radio = this.querySelector('input[type="radio"]');
            radio.checked = true;
            
            // Update visual selection
            originOptions.forEach(opt => opt.classList.remove('selected'));
            this.classList.add('selected');
            
            // Show/hide import fields
            if (importFields) {
                if (radio.value === 'Importado') {
                    importFields.classList.remove('hidden');
                    importFields.classList.add('fade-in');
                } else {
                    importFields.classList.add('hidden');
                    const alertFalsificacao = document.getElementById('alertFalsificacao');
                    if (alertFalsificacao) {
                        alertFalsificacao.classList.add('hidden');
                    }
                }
            }
            
            updateFormProgress();
        });
    });
}

function setupEditionSelection() {
    const editionOptions = document.querySelectorAll('.edition-option');
    
    editionOptions.forEach(option => {
        option.addEventListener('click', function() {
            const radio = this.querySelector('input[type="radio"]');
            radio.checked = true;
            
            // Update visual selection
            editionOptions.forEach(opt => opt.classList.remove('selected'));
            this.classList.add('selected');
            
            updateFormProgress();
        });
    });
}

function setupConditionSelection() {
    const conditionSelect = document.getElementById('condicao');
    const conditionDescription = document.getElementById('conditionDescription');
    
    if (conditionSelect && conditionDescription) {
        conditionSelect.addEventListener('change', function() {
            const condition = this.value;
            if (condition && conditionDescriptions[condition]) {
                conditionDescription.textContent = conditionDescriptions[condition];
                conditionDescription.style.color = 'var(--color-success)';
            } else {
                conditionDescription.textContent = 'Selecione uma condição para ver a descrição';
                conditionDescription.style.color = 'var(--color-text-secondary)';
            }
            
            updateFormProgress();
        });
    }
}

function setupCountrySelection() {
    const paisSelect = document.getElementById('pais');
    const alertFalsificacao = document.getElementById('alertFalsificacao');
    
    if (paisSelect && alertFalsificacao) {
        paisSelect.addEventListener('change', function() {
            const paisesAlerta = ['Rússia', 'Argentina', 'China'];
            if (paisesAlerta.includes(this.value)) {
                alertFalsificacao.classList.remove('hidden');
                alertFalsificacao.classList.add('slide-up');
            } else {
                alertFalsificacao.classList.add('hidden');
            }
        });
    }
}

function setupAutoSave() {
    if (form) {
        const inputs = form.querySelectorAll('input, select, textarea');
        
        inputs.forEach(input => {
            input.addEventListener('change', function() {
                saveFormData();
                updateFormProgress();
            });
        });
    }
}

function changeStep(direction) {
    const newStep = currentStep + direction;
    
    if (newStep < 1 || newStep > totalSteps) return;
    
    if (direction > 0 && !validateCurrentStep()) {
        return;
    }
    
    currentStep = newStep;
    updateStepVisibility();
    updateProgressIndicator();
    updateNavigationButtons();
    
    // Scroll to top
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function validateCurrentStep() {
    const currentStepElement = document.querySelector(`.form-step[data-step="${currentStep}"]`);
    if (!currentStepElement) return true;
    
    const requiredFields = currentStepElement.querySelectorAll('[required]');
    
    let isValid = true;
    
    requiredFields.forEach(field => {
        if (!field.value.trim()) {
            field.focus();
            showAlert('Por favor, preencha todos os campos obrigatórios.', 'error');
            isValid = false;
            return false;
        }
    });
    
    // Additional validations per step
    switch (currentStep) {
        case 1:
            isValid = validateStep1();
            break;
        case 2:
            isValid = validateStep2();
            break;
        case 3:
            isValid = validateStep3();
            break;
    }
    
    return isValid;
}

function validateStep1() {
    const artista = document.getElementById('artista');
    const album = document.getElementById('album');
    const ano = document.getElementById('ano');
    
    if (!artista || !album || !ano) return true;
    
    if (!artista.value.trim() || !album.value.trim() || !ano.value) {
        showAlert('Preencha todos os campos obrigatórios da seção básica.', 'error');
        return false;
    }
    
    const currentYear = new Date().getFullYear();
    if (ano.value < 1900 || ano.value > currentYear + 1) {
        showAlert('Ano de lançamento inválido.', 'error');
        return false;
    }
    
    return true;
}

function validateStep2() {
    const origem = document.querySelector('input[name="origem"]:checked');
    if (!origem) return true;
    
    if (origem.value === 'Importado') {
        const pais = document.getElementById('pais');
        const continente = document.getElementById('continente');
        
        if (pais && continente && (!pais.value || !continente.value)) {
            showAlert('Para discos importados, selecione o país e continente.', 'error');
            return false;
        }
    }
    
    return true;
}

function validateStep3() {
    const condicao = document.getElementById('condicao');
    if (!condicao) return true;
    
    if (!condicao.value) {
        showAlert('Selecione a condição do disco.', 'error');
        return false;
    }
    
    return true;
}

function updateStepVisibility() {
    const steps = document.querySelectorAll('.form-step');
    
    steps.forEach((step, index) => {
        if (index + 1 === currentStep) {
            step.classList.add('active');
        } else {
            step.classList.remove('active');
        }
    });
}

function updateProgressIndicator() {
    const progressSteps = document.querySelectorAll('.progress-step');
    
    progressSteps.forEach((step, index) => {
        const stepNumber = index + 1;
        
        if (stepNumber < currentStep) {
            step.classList.add('completed');
            step.classList.remove('active');
        } else if (stepNumber === currentStep) {
            step.classList.add('active');
            step.classList.remove('completed');
        } else {
            step.classList.remove('active', 'completed');
        }
    });
}

function updateNavigationButtons() {
    if (prevBtn) prevBtn.disabled = currentStep === 1;
    
    if (nextBtn && submitBtn) {
        if (currentStep === totalSteps) {
            nextBtn.style.display = 'none';
            submitBtn.style.display = 'flex';
        } else {
            nextBtn.style.display = 'flex';
            submitBtn.style.display = 'none';
        }
    }
}

function updateFormProgress() {
    // Visual feedback for form completion
    const completedSteps = document.querySelectorAll('.progress-step.completed').length;
    const progressPercentage = (completedSteps / totalSteps) * 100;
    
    // You can add a progress bar here if needed
}

function saveFormData() {
    if (!form) return;
    
    // Save current form state to localStorage
    const formData = new FormData(form);
    const data = {};
    
    for (let [key, value] of formData.entries()) {
        data[key] = value;
    }
    
    localStorage.setItem('trackbox_form_draft', JSON.stringify(data));
}

function loadFormData() {
    if (!form) return;
    
    // Load saved form data
    const savedData = localStorage.getItem('trackbox_form_draft');
    if (savedData) {
        const data = JSON.parse(savedData);
        
        Object.keys(data).forEach(key => {
            const field = form.querySelector(`[name="${key}"]`);
            if (field) {
                field.value = data[key];
            }
        });
    }
}

function handleFormSubmission(e) {
    e.preventDefault();
    
    if (!validateCurrentStep()) {
        return;
    }
    
    // Show loading state
    const originalText = submitBtn ? submitBtn.innerHTML : '';
    if (submitBtn) {
        submitBtn.innerHTML = '<i class="fas fa-spinner spinner"></i> Salvando...';
        submitBtn.disabled = true;
    }
    
    // Simulate API delay
    setTimeout(() => {
        try {
            const discoData = collectFormData();
            saveDiscoToStorage(discoData);
            
            // Clear draft
            localStorage.removeItem('trackbox_form_draft');
            
            showAlert('Disco cadastrado com sucesso!', 'success');
            
            setTimeout(() => {
                window.location.href = 'colecao.html';
            }, 2000);
            
        } catch (error) {
            console.error('Erro ao salvar disco:', error);
            showAlert('Erro ao salvar o disco. Tente novamente.', 'error');
            
            if (submitBtn) {
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            }
        }
    }, 1500);
}

function collectFormData() {
    const session = JSON.parse(localStorage.getItem('trackbox_session') || sessionStorage.getItem('trackbox_session') || '{}');
    
    const tipo = document.querySelector('input[name="tipo"]:checked')?.value || '';
    const artista = document.getElementById('artista')?.value.trim() || '';
    const album = document.getElementById('album')?.value.trim() || '';
    const ano = parseInt(document.getElementById('ano')?.value) || 0;
    const gravadora = document.getElementById('gravadora')?.value.trim() || '';
    const origem = document.querySelector('input[name="origem"]:checked')?.value || '';
    const edicao = document.querySelector('input[name="edicao"]:checked')?.value || '';
    const condicao = document.getElementById('condicao')?.value || '';
    const lacrado = document.getElementById('is_sealed')?.checked || false;
    
    // Conditional fields
    const pais = origem === 'Importado' ? (document.getElementById('pais')?.value || '') : '';
    const continente = origem === 'Importado' ? (document.getElementById('continente')?.value || '') : '';
    
    // Extras
    const extrasCheckboxes = document.querySelectorAll('input[name="extras"]:checked');
    const extras = Array.from(extrasCheckboxes).map(cb => cb.value);
    
    // BoxSet fields
    const edicaoLimitada = tipo === 'BoxSet' ? (document.getElementById('edicaoLimitada')?.checked || false) : false;
    const numeroEdicao = tipo === 'BoxSet' ? (document.getElementById('numeroEdicao')?.value.trim() || '') : '';
    const brindos = tipo === 'BoxSet' ? (document.getElementById('brindos')?.value.trim() || '') : '';
    
    const observacoes = document.getElementById('observacoes')?.value.trim() || '';
    
    return {
        id: Date.now(),
        userId: session.userId || '',
        tipo,
        artista,
        album,
        ano,
        gravadora,
        origem,
        pais,
        continente,
        edicao,
        condicao,
        lacrado,
        extras,
        edicaoLimitada,
        numeroEdicao,
        brindos,
        observacoes,
        cadastradoEm: new Date().toISOString()
    };
}

function saveDiscoToStorage(disco) {
    const discos = JSON.parse(localStorage.getItem('trackbox_discos') || '[]');
    discos.push(disco);
    localStorage.setItem('trackbox_discos', JSON.stringify(discos));
}

function showAlert(message, type) {
    if (!alertContainer) return;
    
    const alertHTML = `
        <div class="alert alert-${type}">
            <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-triangle' : 'info-circle'}"></i>
            <div>
                <span>${message}</span>
            </div>
        </div>
    `;
    alertContainer.innerHTML = alertHTML;
    
    // Auto-hide alert
    setTimeout(() => {
        const alert = alertContainer.querySelector('.alert');
        if (alert) {
            alert.style.opacity = '0';
            alert.style.transform = 'translateY(-10px)';
            setTimeout(() => {
                alertContainer.innerHTML = '';
            }, 300);
        }
    }, 5000);
    
    // Scroll to alert
    alertContainer.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}

function checkAuthentication() {
    const session = localStorage.getItem('trackbox_session') || sessionStorage.getItem('trackbox_session');
    if (!session) {
        showAlert('Você precisa estar logado para acessar esta página!', 'error');
        setTimeout(() => window.location.href = 'login.html', 2000);
        return false;
    }
    return true;
}

function loadUserInfo() {
    const session = localStorage.getItem('trackbox_session') || sessionStorage.getItem('trackbox_session');
    if (session) {
        const sessionData = JSON.parse(session);
        const userName = document.getElementById('userName');
        if (userName) {
            userName.textContent = sessionData.name || 'Usuário';
        }
    }
}

function logout() {
    localStorage.removeItem('trackbox_session');
    sessionStorage.removeItem('trackbox_session');
    localStorage.removeItem('trackbox_form_draft');
    window.location.href = 'index.html';
}

// Progress step click navigation
document.querySelectorAll('.progress-step').forEach((step, index) => {
    step.addEventListener('click', function() {
        const targetStep = index + 1;
        if (targetStep < currentStep || (targetStep === currentStep + 1 && validateCurrentStep())) {
            currentStep = targetStep;
            updateStepVisibility();
            updateProgressIndicator();
            updateNavigationButtons();
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }
    });
});

// Keyboard shortcuts
document.addEventListener('keydown', function(e) {
    if (e.ctrlKey || e.metaKey) {
        switch (e.key) {
            case 'ArrowLeft':
                e.preventDefault();
                if (currentStep > 1) changeStep(-1);
                break;
            case 'ArrowRight':
                e.preventDefault();
                if (currentStep < totalSteps) changeStep(1);
                break;
            case 's':
                e.preventDefault();
                if (currentStep === totalSteps && form) {
                    form.dispatchEvent(new Event('submit'));
                }
                break;
        }
    }
});