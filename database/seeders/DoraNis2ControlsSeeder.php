<?php

namespace Database\Seeders;

use App\Models\Control;
use App\Services\ControlService;
use Illuminate\Database\Seeder;

class DoraNis2ControlsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $controlService = app(ControlService::class);

        // DORA Controls (Regulation (EU) 2022/2554)
        $doraControls = [
            // Article 8 - Governance, risk management and internal control framework
            [
                'article_reference' => 'DORA Art. 8.1',
                'title' => 'Governance and risk management framework',
                'description' => 'Establish and maintain a governance and risk management framework',
                'category' => 'Governance',
            ],
            [
                'article_reference' => 'DORA Art. 8.2',
                'title' => 'Internal control framework',
                'description' => 'Establish and maintain an internal control framework',
                'category' => 'Governance',
            ],
            [
                'article_reference' => 'DORA Art. 8.3',
                'title' => 'Risk management function',
                'description' => 'Establish a risk management function with appropriate expertise',
                'category' => 'Risk Management',
            ],
            [
                'article_reference' => 'DORA Art. 8.4',
                'title' => 'Internal audit function',
                'description' => 'Establish an internal audit function',
                'category' => 'Governance',
            ],
            // Article 9 - ICT risk management
            [
                'article_reference' => 'DORA Art. 9.1',
                'title' => 'ICT risk management framework',
                'description' => 'Establish and maintain an ICT risk management framework',
                'category' => 'Risk Management',
            ],
            [
                'article_reference' => 'DORA Art. 9.2',
                'title' => 'ICT risk identification and assessment',
                'description' => 'Identify and assess ICT risks on a continuous basis',
                'category' => 'Risk Management',
            ],
            [
                'article_reference' => 'DORA Art. 9.3',
                'title' => 'ICT risk mitigation',
                'description' => 'Implement appropriate measures to mitigate ICT risks',
                'category' => 'Risk Management',
            ],
            // Article 10 - ICT-related incident management
            [
                'article_reference' => 'DORA Art. 10.1',
                'title' => 'ICT incident management process',
                'description' => 'Establish and maintain an ICT incident management process',
                'category' => 'Incident Response',
            ],
            [
                'article_reference' => 'DORA Art. 10.2',
                'title' => 'ICT incident classification',
                'description' => 'Classify ICT incidents according to their severity',
                'category' => 'Incident Response',
            ],
            [
                'article_reference' => 'DORA Art. 10.3',
                'title' => 'ICT incident reporting',
                'description' => 'Report major ICT incidents to competent authorities',
                'category' => 'Incident Response',
            ],
            // Article 11 - Business continuity management
            [
                'article_reference' => 'DORA Art. 11.1',
                'title' => 'Business continuity policy',
                'description' => 'Establish and maintain a business continuity policy',
                'category' => 'Business Continuity',
            ],
            [
                'article_reference' => 'DORA Art. 11.2',
                'title' => 'Business continuity plan',
                'description' => 'Develop and maintain business continuity plans',
                'category' => 'Business Continuity',
            ],
            // Article 12 - Information and communication technology (ICT) operations
            [
                'article_reference' => 'DORA Art. 12.1',
                'title' => 'ICT operations management',
                'description' => 'Manage ICT operations to ensure availability and performance',
                'category' => 'ICT Operations',
            ],
            [
                'article_reference' => 'DORA Art. 12.2',
                'title' => 'Change management',
                'description' => 'Establish change management procedures for ICT systems',
                'category' => 'ICT Operations',
            ],
            // Article 13 - Information and communication technology (ICT) security
            [
                'article_reference' => 'DORA Art. 13.1',
                'title' => 'ICT security framework',
                'description' => 'Establish and maintain an ICT security framework',
                'category' => 'Security',
            ],
            [
                'article_reference' => 'DORA Art. 13.2',
                'title' => 'Access control',
                'description' => 'Implement access control measures',
                'category' => 'Security',
            ],
            [
                'article_reference' => 'DORA Art. 13.3',
                'title' => 'Cryptographic controls',
                'description' => 'Implement appropriate cryptographic controls',
                'category' => 'Security',
            ],
            // Article 14 - Testing
            [
                'article_reference' => 'DORA Art. 14.1',
                'title' => 'ICT testing program',
                'description' => 'Establish and maintain an ICT testing program',
                'category' => 'Testing',
            ],
            [
                'article_reference' => 'DORA Art. 14.2',
                'title' => 'Penetration testing',
                'description' => 'Conduct regular penetration testing',
                'category' => 'Testing',
            ],
            // Article 15 - Third-party risk management
            [
                'article_reference' => 'DORA Art. 15.1',
                'title' => 'Third-party risk management framework',
                'description' => 'Establish and maintain a third-party risk management framework',
                'category' => 'Third-Party Risk',
            ],
            [
                'article_reference' => 'DORA Art. 15.2',
                'title' => 'Third-party due diligence',
                'description' => 'Conduct due diligence on third-party service providers',
                'category' => 'Third-Party Risk',
            ],
            // Article 16 - Information sharing
            [
                'article_reference' => 'DORA Art. 16.1',
                'title' => 'Information sharing arrangements',
                'description' => 'Establish information sharing arrangements with competent authorities',
                'category' => 'Information Sharing',
            ],
        ];

        // NIS2 Controls (Directive (EU) 2022/2555) - Article 21
        $nis2Controls = [
            [
                'article_reference' => 'NIS2 Art. 21.1',
                'title' => 'Risk management measures',
                'description' => 'Take appropriate and proportionate technical, operational and organisational measures to manage the risks posed to the security of network and information systems',
                'category' => 'Risk Management',
            ],
            [
                'article_reference' => 'NIS2 Art. 21.2',
                'title' => 'Incident handling',
                'description' => 'Take appropriate measures to prevent and minimise the impact of incidents',
                'category' => 'Incident Response',
            ],
            [
                'article_reference' => 'NIS2 Art. 21.3',
                'title' => 'Business continuity and crisis management',
                'description' => 'Ensure business continuity and crisis management',
                'category' => 'Business Continuity',
            ],
            [
                'article_reference' => 'NIS2 Art. 21.4',
                'title' => 'Supply chain security',
                'description' => 'Ensure supply chain security',
                'category' => 'Third-Party Risk',
            ],
            [
                'article_reference' => 'NIS2 Art. 21.5',
                'title' => 'Security in network and information systems acquisition',
                'description' => 'Ensure security in network and information systems acquisition, development and maintenance',
                'category' => 'Security',
            ],
            [
                'article_reference' => 'NIS2 Art. 21.6',
                'title' => 'Policies and procedures',
                'description' => 'Adopt policies and procedures to assess the effectiveness of cybersecurity risk management measures',
                'category' => 'Governance',
            ],
            [
                'article_reference' => 'NIS2 Art. 21.7',
                'title' => 'Basic cyber hygiene practices',
                'description' => 'Apply basic cyber hygiene practices',
                'category' => 'Security',
            ],
            [
                'article_reference' => 'NIS2 Art. 21.8',
                'title' => 'Training and awareness',
                'description' => 'Provide training and awareness raising for employees',
                'category' => 'Governance',
            ],
        ];

        // Import DORA controls
        $doraImported = $controlService->importStandardControls($doraControls, 'DORA');
        $this->command->info("Imported {$doraImported} DORA controls");

        // Import NIS2 controls
        $nis2Imported = $controlService->importStandardControls($nis2Controls, 'NIS2');
        $this->command->info("Imported {$nis2Imported} NIS2 controls");

        // ISO 27001 Controls (ISO/IEC 27001:2022 - Annex A)
        $iso27001Controls = [
            // A.5 - Information Security Policies
            [
                'article_reference' => 'A.5.1',
                'title' => 'Policies for information security',
                'description' => 'A set of policies for information security shall be defined, approved by management, published and communicated to employees and relevant external parties',
                'category' => 'Information Security Policies',
            ],
            [
                'article_reference' => 'A.5.2',
                'title' => 'Review of the policies for information security',
                'description' => 'The policies for information security shall be reviewed at planned intervals or if significant changes occur',
                'category' => 'Information Security Policies',
            ],
            // A.6 - Organization of Information Security
            [
                'article_reference' => 'A.6.1',
                'title' => 'Information security roles and responsibilities',
                'description' => 'All information security responsibilities shall be defined and allocated',
                'category' => 'Organization of Information Security',
            ],
            [
                'article_reference' => 'A.6.2',
                'title' => 'Segregation of duties',
                'description' => 'Conflicting duties and conflicting areas of responsibility shall be segregated',
                'category' => 'Organization of Information Security',
            ],
            [
                'article_reference' => 'A.6.3',
                'title' => 'Contact with authorities',
                'description' => 'Appropriate contacts with relevant authorities shall be maintained',
                'category' => 'Organization of Information Security',
            ],
            [
                'article_reference' => 'A.6.4',
                'title' => 'Contact with special interest groups',
                'description' => 'Appropriate contacts with special interest groups or other specialist security forums and professional associations shall be maintained',
                'category' => 'Organization of Information Security',
            ],
            [
                'article_reference' => 'A.6.5',
                'title' => 'Information security in project management',
                'description' => 'Information security shall be addressed in project management, regardless of the type of the project',
                'category' => 'Organization of Information Security',
            ],
            // A.7 - Human Resource Security
            [
                'article_reference' => 'A.7.1',
                'title' => 'Screening',
                'description' => 'Background verification checks on all candidates for employment shall be carried out in accordance with relevant laws, regulations and ethics, and proportional to the business requirements, the classification of the information to be accessed and the perceived risks',
                'category' => 'Human Resource Security',
            ],
            [
                'article_reference' => 'A.7.2',
                'title' => 'Terms and conditions of employment',
                'description' => 'The contractual agreements with employees and contractors shall state their and the organization\'s responsibilities for information security',
                'category' => 'Human Resource Security',
            ],
            [
                'article_reference' => 'A.7.3',
                'title' => 'Information security awareness, education and training',
                'description' => 'All employees of the organization and, where relevant, contractors shall receive appropriate awareness education and training and regular updates in organizational policies and procedures, as relevant for their job function',
                'category' => 'Human Resource Security',
            ],
            [
                'article_reference' => 'A.7.4',
                'title' => 'Disciplinary process',
                'description' => 'A formal and communicated disciplinary process shall be in place to take action against employees who have committed an information security breach',
                'category' => 'Human Resource Security',
            ],
            [
                'article_reference' => 'A.7.5',
                'title' => 'Responsibilities after termination or change of employment',
                'description' => 'Information security responsibilities and duties that remain valid after termination or change of employment shall be defined, enforced and communicated to relevant personnel',
                'category' => 'Human Resource Security',
            ],
            // A.8 - Asset Management
            [
                'article_reference' => 'A.8.1',
                'title' => 'Inventory of information and other associated assets',
                'description' => 'An inventory of information and other associated assets, including owners, shall be developed and maintained',
                'category' => 'Asset Management',
            ],
            [
                'article_reference' => 'A.8.2',
                'title' => 'Ownership of assets',
                'description' => 'Information and other associated assets, and acceptable use of assets, shall be identified and documented with their ownership',
                'category' => 'Asset Management',
            ],
            [
                'article_reference' => 'A.8.3',
                'title' => 'Acceptable use of information and other associated assets',
                'description' => 'Rules for the acceptable use and procedures for handling information and other associated assets shall be identified, documented and implemented',
                'category' => 'Asset Management',
            ],
            [
                'article_reference' => 'A.8.4',
                'title' => 'Return of assets',
                'description' => 'All employees and external party users shall return all of the organizational assets in their possession upon termination of their employment, contract or agreement',
                'category' => 'Asset Management',
            ],
            [
                'article_reference' => 'A.8.5',
                'title' => 'Disposal of assets',
                'description' => 'Assets shall be disposed of securely when no longer required, using formal procedures',
                'category' => 'Asset Management',
            ],
            [
                'article_reference' => 'A.8.6',
                'title' => 'Management of removable media',
                'description' => 'Procedures shall be implemented for the management of removable media in accordance with the classification scheme adopted by the organization',
                'category' => 'Asset Management',
            ],
            [
                'article_reference' => 'A.8.7',
                'title' => 'Secure disposal or re-use of equipment',
                'description' => 'Items of equipment containing storage media shall be verified to ensure that any sensitive data and licensed software has been removed or securely overwritten prior to disposal or re-use',
                'category' => 'Asset Management',
            ],
            [
                'article_reference' => 'A.8.8',
                'title' => 'Unattended user equipment',
                'description' => 'Users shall ensure that unattended equipment has appropriate protection',
                'category' => 'Asset Management',
            ],
            [
                'article_reference' => 'A.8.9',
                'title' => 'Configuration management',
                'description' => 'Configuration management of hardware, software, services and networks shall be implemented',
                'category' => 'Asset Management',
            ],
            [
                'article_reference' => 'A.8.10',
                'title' => 'Information deletion',
                'description' => 'Information stored in information systems, devices or in any other storage media shall be deleted when no longer required',
                'category' => 'Asset Management',
            ],
            [
                'article_reference' => 'A.8.11',
                'title' => 'Data masking',
                'description' => 'Data masking shall be used in accordance with the organization\'s topic-specific policy on access control and other related topic-specific policies',
                'category' => 'Asset Management',
            ],
            [
                'article_reference' => 'A.8.12',
                'title' => 'Data leakage prevention',
                'description' => 'Data leakage prevention measures shall be applied to systems, networks and any other devices that process, store or transmit sensitive information',
                'category' => 'Asset Management',
            ],
            // A.9 - Access Control
            [
                'article_reference' => 'A.9.1',
                'title' => 'Access control policy',
                'description' => 'An access control policy shall be established, documented and reviewed based on business and information security requirements',
                'category' => 'Access Control',
            ],
            [
                'article_reference' => 'A.9.2',
                'title' => 'User registration and de-registration',
                'description' => 'The allocation and use of access rights shall be managed through a formal user registration and de-registration process',
                'category' => 'Access Control',
            ],
            [
                'article_reference' => 'A.9.3',
                'title' => 'User access provisioning',
                'description' => 'A formal user access provisioning process shall be implemented to assign or revoke access rights for all user types to all systems and services',
                'category' => 'Access Control',
            ],
            [
                'article_reference' => 'A.9.4',
                'title' => 'Management of privileged access rights',
                'description' => 'The allocation and use of privileged access rights shall be restricted and controlled',
                'category' => 'Access Control',
            ],
            [
                'article_reference' => 'A.9.5',
                'title' => 'Management of secret authentication information of users',
                'description' => 'The allocation of secret authentication information shall be controlled through a formal management process',
                'category' => 'Access Control',
            ],
            [
                'article_reference' => 'A.9.6',
                'title' => 'Review of user access rights',
                'description' => 'Asset owners shall review users\' access rights at regular intervals',
                'category' => 'Access Control',
            ],
            [
                'article_reference' => 'A.9.7',
                'title' => 'Removal or adjustment of access rights',
                'description' => 'The access rights of all employees and external party users to information and information processing facilities shall be removed upon termination of their employment, contract or agreement, or adjusted upon change',
                'category' => 'Access Control',
            ],
            // A.10 - Cryptography
            [
                'article_reference' => 'A.10.1',
                'title' => 'Cryptographic controls',
                'description' => 'A policy on the use of cryptographic controls shall be developed and implemented',
                'category' => 'Cryptography',
            ],
            [
                'article_reference' => 'A.10.2',
                'title' => 'Key management',
                'description' => 'A policy on the use, protection and lifetime of cryptographic keys shall be developed and implemented through their whole lifecycle',
                'category' => 'Cryptography',
            ],
            // A.11 - Physical and Environmental Security
            [
                'article_reference' => 'A.11.1',
                'title' => 'Physical security perimeters',
                'description' => 'Security perimeters shall be defined and used to protect areas that contain either sensitive or critical information and information processing facilities',
                'category' => 'Physical and Environmental Security',
            ],
            [
                'article_reference' => 'A.11.2',
                'title' => 'Physical entry controls',
                'description' => 'Secure areas shall be protected by appropriate entry controls to ensure that only authorized personnel are allowed access',
                'category' => 'Physical and Environmental Security',
            ],
            [
                'article_reference' => 'A.11.3',
                'title' => 'Securing offices, rooms and facilities',
                'description' => 'Physical security for offices, rooms and facilities shall be designed and applied',
                'category' => 'Physical and Environmental Security',
            ],
            [
                'article_reference' => 'A.11.4',
                'title' => 'Physical security monitoring',
                'description' => 'Premises shall be continuously monitored for unusual physical activities and environmental conditions',
                'category' => 'Physical and Environmental Security',
            ],
            [
                'article_reference' => 'A.11.5',
                'title' => 'Protecting against physical and environmental threats',
                'description' => 'Protection against physical and environmental threats, such as natural disasters, malicious attack or accidents, shall be designed and applied',
                'category' => 'Physical and Environmental Security',
            ],
            [
                'article_reference' => 'A.11.6',
                'title' => 'Working in secure areas',
                'description' => 'Procedures for working in secure areas shall be designed and applied',
                'category' => 'Physical and Environmental Security',
            ],
            [
                'article_reference' => 'A.11.7',
                'title' => 'Clear desk and clear screen',
                'description' => 'A clear desk policy for papers and removable storage media and a clear screen policy for information processing facilities shall be adopted',
                'category' => 'Physical and Environmental Security',
            ],
            // A.12 - Operations Security
            [
                'article_reference' => 'A.12.1',
                'title' => 'Documented operating procedures',
                'description' => 'Operating procedures shall be documented, maintained and made available to all personnel who need them',
                'category' => 'Operations Security',
            ],
            [
                'article_reference' => 'A.12.2',
                'title' => 'Change management',
                'description' => 'Changes to information processing facilities and systems shall be controlled',
                'category' => 'Operations Security',
            ],
            [
                'article_reference' => 'A.12.3',
                'title' => 'Capacity management',
                'description' => 'The use of resources shall be monitored, tuned and projections made of future capacity requirements to ensure the required system performance',
                'category' => 'Operations Security',
            ],
            [
                'article_reference' => 'A.12.4',
                'title' => 'Separation of development, testing and operational environments',
                'description' => 'Development, testing and operational environments shall be separated and secured',
                'category' => 'Operations Security',
            ],
            [
                'article_reference' => 'A.12.5',
                'title' => 'Management of technical vulnerabilities',
                'description' => 'Information about technical vulnerabilities of information systems being used shall be obtained in a timely fashion, the organization\'s exposure to such vulnerabilities evaluated and appropriate measures taken to address the associated risk',
                'category' => 'Operations Security',
            ],
            [
                'article_reference' => 'A.12.6',
                'title' => 'Restrictions on software installation and execution',
                'description' => 'Rules governing the installation of software by users shall be established and implemented',
                'category' => 'Operations Security',
            ],
            [
                'article_reference' => 'A.12.7',
                'title' => 'Information systems backup',
                'description' => 'Backup copies of information, software and system images shall be taken and tested regularly in accordance with the topic-specific policy on backup',
                'category' => 'Operations Security',
            ],
            [
                'article_reference' => 'A.12.8',
                'title' => 'Information redundancy',
                'description' => 'Where availability is a requirement, redundancy shall be implemented to satisfy availability requirements',
                'category' => 'Operations Security',
            ],
            [
                'article_reference' => 'A.12.9',
                'title' => 'Configuration management',
                'description' => 'Configurations, including security configurations, of hardware, software, services and networks shall be established, documented, implemented, monitored and reviewed',
                'category' => 'Operations Security',
            ],
            [
                'article_reference' => 'A.12.10',
                'title' => 'Information deletion',
                'description' => 'Information stored in information systems, devices or in any other storage media shall be deleted when no longer required',
                'category' => 'Operations Security',
            ],
            [
                'article_reference' => 'A.12.11',
                'title' => 'Data masking',
                'description' => 'Data masking shall be used in accordance with the organization\'s topic-specific policy on access control and other related topic-specific policies',
                'category' => 'Operations Security',
            ],
            [
                'article_reference' => 'A.12.12',
                'title' => 'Data leakage prevention',
                'description' => 'Data leakage prevention measures shall be applied to systems, networks and any other devices that process, store or transmit sensitive information',
                'category' => 'Operations Security',
            ],
            // A.13 - Communications Security
            [
                'article_reference' => 'A.13.1',
                'title' => 'Network controls',
                'description' => 'Networks shall be managed and controlled to protect information in systems and applications',
                'category' => 'Communications Security',
            ],
            [
                'article_reference' => 'A.13.2',
                'title' => 'Information transfer policies and procedures',
                'description' => 'Policies and procedures shall be established for the protection of information transferred within an organization and with any external entity',
                'category' => 'Communications Security',
            ],
            // A.14 - System Acquisition, Development and Maintenance
            [
                'article_reference' => 'A.14.1',
                'title' => 'Information security requirements analysis and specification',
                'description' => 'Information security requirements shall be identified and agreed prior to the development or acquisition of information systems',
                'category' => 'System Acquisition, Development and Maintenance',
            ],
            [
                'article_reference' => 'A.14.2',
                'title' => 'Securing application services on public networks',
                'description' => 'Information involved in application services passing over public networks shall be protected from fraudulent activity, contract dispute and unauthorized disclosure and modification',
                'category' => 'System Acquisition, Development and Maintenance',
            ],
            [
                'article_reference' => 'A.14.3',
                'title' => 'Protecting application services transactions',
                'description' => 'Information involved in application service transactions shall be protected to prevent incomplete transmission, mis-routing, unauthorized message alteration, unauthorized disclosure, unauthorized message duplication or replay',
                'category' => 'System Acquisition, Development and Maintenance',
            ],
            // A.15 - Supplier Relationships
            [
                'article_reference' => 'A.15.1',
                'title' => 'Information security in supplier relationships',
                'description' => 'Information security requirements for mitigating the risks associated with supplier\'s access to the organization\'s assets shall be agreed with the supplier and documented',
                'category' => 'Supplier Relationships',
            ],
            [
                'article_reference' => 'A.15.2',
                'title' => 'Addressing information security within supplier agreements',
                'description' => 'All relevant information security requirements shall be established and agreed with each supplier that may access, process, store, communicate, or provide IT infrastructure components for, the organization\'s information and information systems',
                'category' => 'Supplier Relationships',
            ],
            [
                'article_reference' => 'A.15.3',
                'title' => 'Information and communication technology supply chain',
                'description' => 'Agreements with suppliers shall include requirements to address the information security risks associated with information and communications technology services and product supply chain',
                'category' => 'Supplier Relationships',
            ],
            // A.16 - Information Security Incident Management
            [
                'article_reference' => 'A.16.1',
                'title' => 'Management of information security incidents and improvements',
                'description' => 'Responsibilities and procedures shall be established to ensure a quick, effective and orderly response to information security incidents',
                'category' => 'Information Security Incident Management',
            ],
            [
                'article_reference' => 'A.16.2',
                'title' => 'Reporting information security events',
                'description' => 'Information security events shall be reported through appropriate management channels as quickly as possible',
                'category' => 'Information Security Incident Management',
            ],
            [
                'article_reference' => 'A.16.3',
                'title' => 'Reporting information security weaknesses',
                'description' => 'All employees, contractors and external parties shall be required to note and report any observed or suspected information security weaknesses in systems or services',
                'category' => 'Information Security Incident Management',
            ],
            [
                'article_reference' => 'A.16.4',
                'title' => 'Assessment of and decision on information security events',
                'description' => 'Information security events shall be assessed and it shall be decided if they are to be classified as information security incidents',
                'category' => 'Information Security Incident Management',
            ],
            [
                'article_reference' => 'A.16.5',
                'title' => 'Response to information security incidents',
                'description' => 'Information security incidents shall be responded to in accordance with the documented procedures',
                'category' => 'Information Security Incident Management',
            ],
            [
                'article_reference' => 'A.16.6',
                'title' => 'Learning from information security incidents',
                'description' => 'Knowledge gained from analyzing and resolving information security incidents shall be used to reduce the likelihood or impact of future incidents',
                'category' => 'Information Security Incident Management',
            ],
            [
                'article_reference' => 'A.16.7',
                'title' => 'Collection of evidence',
                'description' => 'The organization shall define and apply procedures for the identification, collection, acquisition and preservation of information, which can serve as evidence',
                'category' => 'Information Security Incident Management',
            ],
            // A.17 - Information Security Aspects of Business Continuity Management
            [
                'article_reference' => 'A.17.1',
                'title' => 'Planning information security continuity',
                'description' => 'The organization shall determine its requirements for information security and the continuity of information security management in adverse situations, e.g. during a crisis or disaster',
                'category' => 'Business Continuity Management',
            ],
            [
                'article_reference' => 'A.17.2',
                'title' => 'Implementing information security continuity',
                'description' => 'The organization shall establish, document, implement and maintain processes, procedures and controls to ensure the required level of continuity for information security during an adverse situation',
                'category' => 'Business Continuity Management',
            ],
            [
                'article_reference' => 'A.17.3',
                'title' => 'Verify, review and evaluate information security continuity',
                'description' => 'The organization shall verify the established and implemented information security continuity controls at regular intervals in order to ensure their effectiveness during adverse situations',
                'category' => 'Business Continuity Management',
            ],
            // A.18 - Compliance
            [
                'article_reference' => 'A.18.1',
                'title' => 'Identification of applicable legislation, statutory, regulatory and contractual requirements',
                'description' => 'All relevant legislative, statutory, regulatory and contractual requirements and the organization\'s approach to meet these requirements shall be identified, documented and kept up to date for each information system and the organization',
                'category' => 'Compliance',
            ],
            [
                'article_reference' => 'A.18.2',
                'title' => 'Intellectual property rights',
                'description' => 'Appropriate procedures shall be implemented to ensure compliance with legislative, regulatory and contractual requirements related to intellectual property rights',
                'category' => 'Compliance',
            ],
            [
                'article_reference' => 'A.18.3',
                'title' => 'Protection of records',
                'description' => 'Records shall be protected from loss, destruction, falsification, unauthorized access and unauthorized release, in accordance with legislative, regulatory, contractual and business requirements',
                'category' => 'Compliance',
            ],
            [
                'article_reference' => 'A.18.4',
                'title' => 'Privacy and protection of personally identifiable information',
                'description' => 'Privacy and protection of personally identifiable information shall be ensured as required in relevant legislation and regulation where applicable',
                'category' => 'Compliance',
            ],
            [
                'article_reference' => 'A.18.5',
                'title' => 'Independent review of information security',
                'description' => 'The organization\'s approach to managing information security and its implementation (i.e. control objectives, controls, policies, processes and procedures for information security) shall be reviewed independently at planned intervals or when significant changes occur',
                'category' => 'Compliance',
            ],
            [
                'article_reference' => 'A.18.6',
                'title' => 'Compliance with policies, rules and standards for information security',
                'description' => 'Compliance with the organization\'s information security policies, topic-specific policies, rules and standards shall be regularly reviewed',
                'category' => 'Compliance',
            ],
            [
                'article_reference' => 'A.18.7',
                'title' => 'Documented operating procedures',
                'description' => 'Operating procedures shall be documented, maintained and made available to all personnel who need them',
                'category' => 'Compliance',
            ],
        ];

        // Import ISO 27001 controls
        $iso27001Imported = $controlService->importStandardControls($iso27001Controls, 'ISO27001');
        $this->command->info("Imported {$iso27001Imported} ISO 27001 controls");
    }
}
